<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
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
     * @param string $categoryTitle The title of the category to fetch images from.
     * @param string $folderName The name of the folder in S3 to store the images.
     * @return array<string> The list of URLs of the uploaded images.
     * @throws Exception
     */
    public function fetchAndUploadImages(string $categoryTitle, string $folderName): array
    {
        $imageUrls = [];

        try {
            // Fetch category members from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'list' => 'categorymembers',
                'format' => 'json',
                'prop' => 'imageinfo',
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
                            // Check if image already exists in S3
                            $imagePath = 'images/' . $folderName . '/' . basename($imageUrl);
                            $s3 = Storage::disk('s3');
                            if (!$s3->exists($imagePath)) {
                                // Download the image
                                $imageResponse = Http::get($imageUrl);
                                if ($imageResponse->failed()) {
                                    \Log::error('Error downloading image: ' . $imageUrl);
                                    continue;
                                }
                                $imageContent = $imageResponse->body();

                                // Upload the image to AWS S3
                                $s3->put($imagePath, $imageContent);
                            }

                            // Add the URL to the list
                            $imageUrls[] = $s3->url($imagePath);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Handle exceptions
            \Log::error('Error fetching or uploading images: ' . $e->getMessage());
        }

        return $imageUrls;
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
}
