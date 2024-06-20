<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

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
            \Log::info("message: Fetching images from $categoryTitle");
        } else {
            \Log::info("message: No wikimedia commons in tags");
            return $images;
        }

        try {
            // Fetch category members from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'list' => 'categorymembers',
                'format' => 'json',
                'prop' => 'info',
                'cmtitle' => $categoryTitle,
                'cmlimit' => 'max',
                'iiprop' => 'url',
            ]);

            $data = $response->json();

            if (isset($data['query']['categorymembers'])) {
                $pages = $data['query']['categorymembers'];

                foreach ($pages as $page) {
                    if ($page['ns'] === 6 && isset($page['title'])) { //ns=6 is for images
                        // Fetch image info
                        $imageUrl = $this->getImageUrl($page['title']);
                        if ($imageUrl) {
                            try {
                                $imagePath = 'images/' . $folderName . '/' . basename($imageUrl);
                                $imageAWSurl = $this->uploadToAWS($imagePath, $imageUrl);
                            } catch (Exception $e) {
                                \Log::error('Error uploading image: ' . $imageUrl);
                                continue;
                            }
                            // Add the URL to the list
                            $images['urls'] = array_merge($images['urls'], [$imageAWSurl]);
                        }
                    }
                }
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
                'prop' => 'info|imageinfo',
                'iiprop' => 'url|descriptionurl|extmetadata',
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

    private function uploadToAWS(string $imagePath, string $imageUrl): string
    {
        $s3 = Storage::disk('s3');
        // Check if image already exists in S3
        if (!$s3->exists($imagePath)) {
            // Download the image
            $imageResponse = Http::get($imageUrl);
            if ($imageResponse->failed()) {
                \Log::error('Error downloading image: ' . $imageUrl);
                throw new Exception('Error downloading image: ' . $imageUrl);
            }
            $imageContent = $imageResponse->body();

            // Upload the image to AWS S3
            $s3->put($imagePath, $imageContent);

            return $s3->url($imagePath);
        }
    }
}
