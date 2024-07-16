<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\DataFetchers\WikiDataFetcher;
use App\Services\DataFetchers\WikipediaFetcher;

class WikimediaService
{
    /**
     * Logger
     *
     * @var Log
     */
    protected $logger;
    protected $wikidataFetcher;
    protected $wikipediaFetcher;

    public function __construct()
    {
        $this->logger = Log::channel('wikimediaService');
        $this->wikidataFetcher = new WikiDataFetcher();
        $this->wikipediaFetcher = new WikipediaFetcher();
    }

    /**
     * Fetches image URLs for a given category or file in Wikimedia Commons API and uploads them to AWS S3.
     *
     * @param Model $model The model to fetch images for.
     * @return array<string> The list of URLs of the uploaded images.
     * @throws Exception
     */
    public function fetchImages(Model $model): ?array
    {
        $result = [];
        $tags = json_decode($model->tags, true);

        if (isset($tags['wikimedia_commons'])) {
            $wikimediaValue = $tags['wikimedia_commons'];
            $this->logger->info("Fetching images from $wikimediaValue");

            if (strpos($wikimediaValue, 'File:') === 0) {
                // Handle single file case
                $imageData = $this->getImageData($wikimediaValue);
                if ($imageData) {
                    $result['wikimedia_images'] = $imageData;
                }
            } else {
                // Handle category case
                $categoryTitle = str_replace('Category:', '', $wikimediaValue);
                $this->fetchCategoryImages($categoryTitle, $result);
            }
        } else {
            $this->logger->info("No wikimedia commons in tags, looking for images in wikidata or wikipedia...");

            $result = $this->enrichWithWikidataOrWikipedia($tags);
        }

        return $result;
    }

    private function enrichWithWikidataOrWikipedia(array $tags): array
    {
        $result = [];
        if (isset($tags['wikipedia'])) {
            $wikipediaValue = $tags['wikipedia'];
            $this->logger->info("Fetching images from $wikipediaValue");
            $wikipediaData = $this->wikipediaFetcher->fetchData($wikipediaValue);
            if ($wikipediaData) {
                $result['wikipedia_images'] = $wikipediaData['image'];
            }
        }
        if (isset($tags['wikidata'])) {
            $wikidataValue = $tags['wikidata'];
            $this->logger->info("Fetching images from $wikidataValue");
            $wikidataData = $this->wikidataFetcher->fetchData($wikidataValue);
            if ($wikidataData) {
                $result['wikidata_images'] = $wikidataData['image'];
            }
        }

        return $result;
    }

    /**
     * Fetches image URLs for a given category from Wikimedia Commons API and adds them to the result array.
     *
     * @param string $categoryTitle The title of the category.
     * @param array $result The array to add the fetched image URLs to.
     */
    private function fetchCategoryImages(string $categoryTitle, array &$result)
    {
        $result = [];
        try {
            // Fetch category members from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'list' => 'categorymembers',
                'format' => 'json',
                'cmtitle' => "Category:$categoryTitle",
                'cmlimit' => 'max',
                'cmnamespace' => '6', // ns=6 is for images
            ]);

            $data = $response->json();

            if (isset($data['query']['categorymembers'])) {
                $pages = $data['query']['categorymembers'];
                foreach ($pages as $page) {
                    $imageData = $this->getImageData($page['title']);
                    if ($imageData) {
                        $result['wikimedia_images'][] = $imageData;
                    }
                }
            } else {
                $this->logger->info("No images found in $categoryTitle");
            }
        } catch (Exception $e) {
            $this->logger->error('Error fetching category members: ' . $e->getMessage());
        }
    }

    /**
     * Fetches the URL of an image from Wikimedia Commons API.
     *
     * @param string $fileName The name of the image file.
     * @return array|null The URL of the image, or null if not found.
     * @throws Exception
     */
    private function getImageData(string $fileName): ?array
    {
        try {
            // Fetch image URL from Wikimedia Commons API
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'titles' => $fileName,
                'format' => 'json',
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata',
                'iiurlwidth' => 100, // Specifica la larghezza desiderata
                // 'iiurlheight' => 500, // Specifica l'altezza desiderata (opzionale, solo se necessario)
            ]);

            $data = $response->json();

            $pages = $data['query']['pages'];
            foreach ($pages as $page) {
                if (isset($page['imageinfo'][0]['url'])) {
                    $sourceUrl = $page['imageinfo'][0]['url'];
                    $thumbUrl = $page['imageinfo'][0]['thumburl'];
                    return ['source_url' => $sourceUrl, 'thumb_url' =>  $thumbUrl];
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
        if (!Storage::exists($imagePath)) {
            $imageResponse = Http::get($imageUrl);
            if ($imageResponse->failed()) {
                $this->logger->error('Error downloading image: ' . $imageUrl);
                throw new Exception('Error downloading image: ' . $imageUrl);
            }
            $imageContent = $imageResponse->body();
            Storage::put($imagePath, $imageContent);
        }
        return Storage::url($imagePath);
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