<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WikimediaService
{
    /**
     * Logger
     *
     * @var Log
     */
    protected $logger;
    protected $aws;

    public function __construct()
    {
        $this->logger = Log::channel('wikimediaService');
        $this->aws = Storage::disk('s3');
    }

    /**
     * Fetches image URLs for a given category in Wikimedia Commons API and uploads them to AWS S3.
     *
     * @param Model $model The model to fetch images for.
     * @return array<string> The list of URLs of the uploaded images.
     * @throws Exception
     */
    public function fetchAndUploadImages(Model $model): array
    {
        $result = [];
        $tags = json_decode($model->tags, true);

        if (isset($tags['wikimedia_commons'])) {
            $categoryTitle = str_replace('File:', '', $tags['wikimedia_commons']);
            $folderName = $tags['name'] ?? $tags['wikimedia_commons'];
            $this->logger->info("Fetching images from $categoryTitle");
        } else {
            $this->logger->info("No wikimedia commons in tags");
            throw new Exception('No wikimedia commons in tags');
        }

        // Check if the model has an enrichment
        $firstUpdate = !$model->enrichment;
        $localData = $firstUpdate ? [] : json_decode($model->enrichment->data, true);
        $localImages = $firstUpdate ? [] : $localData['images'];
        $localLastUpdate = $firstUpdate ? null : Carbon::parse($localData['last_update_wikimedia_commons']);

        try {
            // Fetch category members from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'list' => 'categorymembers',
                'format' => 'json',
                'cmtitle' => $categoryTitle,
                'cmlimit' => 'max',
                'cmnamespace' => '6', // ns=6 is for images
            ]);

            $data = $response->json();
        } catch (Exception $e) {
            $this->logger->error('Error fetching category members: ' . $e->getMessage());
            throw new Exception('Error fetching category members: ' . $e->getMessage());
        }

        if (isset($data['query']['categorymembers'])) {
            $pages = $data['query']['categorymembers'];
            $newestDateTime = $localLastUpdate;

            foreach ($pages as $page) {
                $imageData = $this->getImageData($page['title']);
                if ($imageData) {
                    $sourceUrl = $imageData['source_url'];
                    $dateTime = Carbon::parse($imageData['dateTime']);

                    if ($firstUpdate) {
                        $awsUrl = $this->uploadToAWS('images/' . $folderName . '/' . basename($sourceUrl), $sourceUrl);
                        $result[] = ['source_url' => $sourceUrl, 'dateTime' => $imageData['dateTime'], 'aws_url' => $awsUrl];
                    } else {
                        $localImage = $this->findLocalImageByUrl($localImages, $sourceUrl);
                        if ($localImage) {
                            if ($dateTime->gt($localLastUpdate)) {
                                $awsUrl = $this->uploadToAWS('images/' . $folderName . '/' . basename($sourceUrl), $sourceUrl);
                                $localImage['aws_url'] = $awsUrl;
                                $localImage['dateTime'] = $imageData['dateTime'];
                                $result[] = $localImage;
                                $newestDateTime = $newestDateTime->lt($dateTime) ? $dateTime : $newestDateTime;
                            } else {
                                $result[] = $localImage;
                            }
                        } else {
                            $awsUrl = $this->uploadToAWS('images/' . $folderName . '/' . basename($sourceUrl), $sourceUrl);
                            $result[] = ['source_url' => $sourceUrl, 'dateTime' => $imageData['dateTime'], 'aws_url' => $awsUrl];
                        }
                    }
                }
            }

            if ($firstUpdate) {
                $result['last_update_wikimedia_commons'] = Carbon::now()->toIso8601String();
            }
            if (!$firstUpdate && $newestDateTime) {
                $result['last_update_wikimedia_commons'] = $newestDateTime->toIso8601String();
            }
        } else {
            $this->logger->info("No images found in $categoryTitle");
            return $result;
        }

        return $result;
    }

    /**
     * Fetches the URL of an image from Wikimedia Commons API.
     *
     * @param string $fileName The name of the image file.
     * @return string|null The URL of the image, or null if not found.
     * @throws Exception
     */
    private function getImageData(string $fileName): ?array
    {
        $res = [];
        try {
            // Fetch image URL from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'titles' => $fileName,
                'format' => 'json',
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata',
            ]);

            $data = $response->json();

            $pages = $data['query']['pages'];
            foreach ($pages as $page) {
                if (isset($page['imageinfo'][0]['url'])) {
                    $sourceUrl = $page['imageinfo'][0]['url'];
                    $dateTime = $page['imageinfo'][0]['extmetadata']['DateTime']['value'];
                    $res = ['source_url' => $sourceUrl, 'dateTime' => $dateTime];
                    return $res;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Error fetching image URL: ' . $e->getMessage());
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
        if (!$this->aws->exists($imagePath)) {
            $imageResponse = Http::get($imageUrl);
            if ($imageResponse->failed()) {
                $this->logger->error('Error downloading image: ' . $imageUrl);
                throw new Exception('Error downloading image: ' . $imageUrl);
            }
            $imageContent = $imageResponse->body();
            $this->aws->put($imagePath, $imageContent);
        }
        return $this->aws->url($imagePath);
    }

    /**
     * Finds a local image by its source URL.
     *
     * @param array $localImages The array of local images.
     * @param string $sourceUrl The source URL of the image.
     * @return array|null The local image data or null if not found.
     */
    private function findLocalImageByUrl(array $localImages, string $sourceUrl): ?array
    {
        foreach ($localImages as $localImage) {
            if ($localImage['source_url'] === $sourceUrl) {
                return $localImage;
            }
        }
        return null;
    }
}
