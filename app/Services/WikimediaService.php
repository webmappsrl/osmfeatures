<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Service class for fetching and uploading images from Wikimedia Commons API.
 */
class WikimediaService
{
    /**
     * Fetches image URLs for a given category in Wikimedia Commons API and uploads them to AWS S3.
     *
     * @param Model $model The model to fetch images for.
     * @return array<string> The list of URLs of the uploaded images.
     * @throws Exception
     */
    public function fetchAndUploadImages(Model $model): array
    {
        // Decode the JSON tags of the model.
        $tags = json_decode($model->tags, true);
        $images = [];

        if (isset($tags['wikimedia_commons'])) {
            $categoryTitle = str_replace('File:', '', $tags['wikimedia_commons']);
            $folderName = $tags['name'] ?? $tags['wikimedia_commons'];
            \Log::info("Fetching images from $categoryTitle");
        } else {
            \Log::info("No wikimedia commons in tags");
            return $images;
        }

        try {
            // Fetch category members from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'list' => 'categorymembers',
                'format' => 'json',
                'cmtitle' => $categoryTitle,
                'cmlimit' => 'max',
            ]);

            $data = $response->json();
            $commonsImages = [];
            if (isset($data['query']['categorymembers'])) {
                $pages = $data['query']['categorymembers'];

                foreach ($pages as $page) {
                    if ($page['ns'] === 6 && isset($page['title'])) { // ns=6 is for images
                        $imageUrl = $this->getImageUrl($page['title']);
                        if ($imageUrl) {
                            $commonsImages[] = $imageUrl;
                        }
                    }
                }
            }

            // Get existing images from S3
            $s3Images = $this->getS3Images($folderName);

            // Calculate images to add and remove
            $imagesToAdd = array_diff($commonsImages, $s3Images);
            $imagesToRemove = array_diff($s3Images, $commonsImages);
            \Log::info("Images to add: " . count($imagesToAdd));
            \Log::info("Images to remove: " . count($imagesToRemove));

            // Flag to check if there were changes
            $hasUpdates = false;

            // Upload new images to AWS S3
            foreach ($imagesToAdd as $imageUrl) {
                try {
                    $imagePath = 'images/' . $folderName . '/' . basename($imageUrl);
                    $imageAWSurl = $this->uploadToAWS($imagePath, $imageUrl);
                    $images['urls'][] = $imageAWSurl;
                    $hasUpdates = true;
                    \Log::info("Added image: $imageAWSurl");
                } catch (Exception $e) {
                    \Log::error('Error uploading image: ' . $imageUrl);
                }
            }

            // Remove obsolete images from AWS S3
            foreach ($imagesToRemove as $imageUrl) {
                try {
                    $imagePath = 'images/' . $folderName . '/' . basename($imageUrl);
                    $this->deleteFromAWS($imagePath);
                    $hasUpdates = true;
                    \Log::info("Removed image: $imageUrl");
                } catch (Exception $e) {
                    \Log::error('Error deleting image: ' . $imageUrl);
                }
            }
            if ($hasUpdates) {
                $lastWikiCommonsUpdate = Carbon::now();
                $images['lastWikiCommonsUpdate'] = $lastWikiCommonsUpdate;
            } else {
                \Log::info('No changes in images');
            }
        } catch (Exception $e) {
            // Handle exceptions
            \Log::error('Error fetching or uploading images: ' . $e->getMessage());
        }

        return $images;
    }

    /**
     * Fetches the URL of an image from Wikimedia Commons API.
     *
     * @param string $fileName The name of the image file.
     * @return string|null The URL of the image, or null if not found.
     * @throws Exception
     */
    private function getImageUrl(string $fileName): ?string
    {
        try {
            // Fetch image URL from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'titles' => $fileName,
                'format' => 'json',
                'prop' => 'imageinfo',
                'iiprop' => 'url',
            ]);

            $data = $response->json();

            $pages = $data['query']['pages'];
            foreach ($pages as $page) {
                if (isset($page['imageinfo'][0]['url'])) {
                    return $page['imageinfo'][0]['url'];
                }
            }
        } catch (Exception $e) {
            \Log::error('Error fetching image URL: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Uploads an image to AWS S3.
     *
     * @param string $imagePath The path where the image should be stored in S3.
     * @param string $imageUrl The URL of the image to upload.
     * @return string The URL of the uploaded image in S3.
     * @throws Exception
     */
    private function uploadToAWS(string $imagePath, string $imageUrl): string
    {
        $s3 = Storage::disk('s3');
        if (!$s3->exists($imagePath)) {
            $imageResponse = Http::get($imageUrl);
            if ($imageResponse->failed()) {
                \Log::error('Error downloading image: ' . $imageUrl);
                throw new Exception('Error downloading image: ' . $imageUrl);
            }
            $imageContent = $imageResponse->body();
            $s3->put($imagePath, $imageContent);
        }
        return $s3->url($imagePath);
    }

    /**
     * Deletes an image from AWS S3.
     *
     * @param string $imagePath The path of the image to delete in S3.
     * @throws Exception
     */
    private function deleteFromAWS(string $imagePath): void
    {
        $s3 = Storage::disk('s3');
        if ($s3->exists($imagePath)) {
            $s3->delete($imagePath);
        }
    }

    /**
     * Gets the list of image URLs stored in a specific folder on AWS S3.
     *
     * @param string $folderName The name of the folder to check.
     * @return array The list of image URLs.
     */
    private function getS3Images(string $folderName): array
    {
        $s3 = Storage::disk('s3');
        $files = $s3->allFiles('images/' . $folderName);
        return array_map(fn ($file) => $s3->url($file), $files);
    }
}
