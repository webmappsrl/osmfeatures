<?php

namespace App\Services;

use OpenAI;
use Exception;
use Carbon\Carbon;
use App\Models\Enrichment;
use App\Services\WikimediaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use App\Services\Generators\OpenAiGenerator;
use App\Services\DataFetchers\WikiDataFetcher;
use App\Services\DataFetchers\WikipediaFetcher;

/**
 * Service for enriching models with OpenAI
 */
class EnrichmentService
{
    protected $wikipediaFetcher;
    protected $wikidataFetcher;
    protected $OpenAiGenerator;

    /**
     * Wikimedia service
     *
     * @var \App\Services\WikimediaService
     */
    protected $wikimediaService;

    /**
     * Logger
     *
     * @var Log
     */

    protected $logger;

    public function __construct()
    {
        $this->wikimediaService = new WikimediaService();
        $this->wikipediaFetcher = new WikipediaFetcher();
        $this->wikidataFetcher = new WikidataFetcher();
        $this->OpenAiGenerator = new OpenAiGenerator();
        $this->logger = Log::channel('enrichment');
    }

    public function enrich(Model $model)
    {
        $this->logger->info('Enriching model: ' . get_class($model) . ' ' . $model->id);

        //initialize json
        $json = [];

        $tags = json_decode($model->tags, true);
        try {
            $existingEnrichment = Enrichment::where('enrichable_id', $model->id)
                ->where('enrichable_type', get_class($model))
                ->first();

            $existingData = $existingEnrichment ? json_decode($existingEnrichment->data, true) : null;
            $fetchedData = $this->fetchDataFromWiki($tags);
            try {
                $shouldUpdateDescription = $this->shouldUpdateDescription($fetchedData, $existingData);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                throw $e;
            }

            if ($shouldUpdateDescription) {
                //update description
                $this->logger->info('Updating description...');
                try {
                    $openAIdescription = $this->OpenAiGenerator->generateDescription($fetchedData, 1800);
                    $openAIdescriptionEn = $this->OpenAiGenerator->translateTo('english', $openAIdescription);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    throw $e;
                }
                $this->logger->info('Description generated');

                //update abstract
                $this->logger->info('Updating abstract...');
                try {
                    $openAIabstract = $this->OpenAiGenerator->generateAbstractFromDescription($openAIdescription, 255);
                    $openAIabstractEn = $this->OpenAiGenerator->translateTo('english', $openAIabstract);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    throw $e;
                }
                $this->logger->info('Abstract generated');

                $wikipediaLastUpdate = $fetchedData['wikipedia']['lastModified'] ?? null;
                $wikidataLastUpdate = $fetchedData['wikidata']['lastModified'] ?? null;

                $json['last_update_wikipedia'] = $wikipediaLastUpdate;
                $json['last_update_wikidata'] = $wikidataLastUpdate;
            } else {
                $json['last_update_wikipedia'] = $existingData['last_update_wikipedia'] ?? null;
                $json['last_update_wikidata'] = $existingData['last_update_wikidata'] ?? null;
            }

            $this->logger->info('Fetching images...');
            try {
                $imageData = $this->wikimediaService->fetchAndUploadImages($model);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $imageData = null;
            }
            $this->logger->info('Images fetched');

            if (isset($imageData['last_update_wikimedia_commons'])) {
                $lastUpdateWikimediaCommons = $imageData['last_update_wikimedia_commons'];
                unset($imageData['last_update_wikimedia_commons']);
            } else {
                $lastUpdateWikimediaCommons = null;
            }

            // Construct the final JSON
            $json['last_update_wikimedia_commons'] = $lastUpdateWikimediaCommons;
            $json['abstract'] = [
                'it' => $shouldUpdateDescription ? $openAIabstract : ($existingData['abstract']['it'] ?? ''),
                'en' => $shouldUpdateDescription ? $openAIabstractEn : ($existingData['abstract']['en'] ?? ''),
            ];
            $json['description'] = [
                'it' => $shouldUpdateDescription ? $openAIdescription : ($existingData['description']['it'] ?? ''),
                'en' => $shouldUpdateDescription ? $openAIdescriptionEn : ($existingData['description']['en'] ?? ''),
            ];
            $json['images'] = $imageData;

            Enrichment::updateOrCreate([
                'enrichable_id' => $model->id,
            ], [
                'enrichable_type' => get_class($model),
                'data' => json_encode($json),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Enrichment failed: ' . $e->getMessage());
            throw new \Exception('Failed to enrich model ' . get_class($model) . ' with osmid ' . $model->osm_id . ': ' . $e->getMessage());
        }

        $this->logger->info('Enrichment successful');
    }

    protected function shouldUpdateDescription(array $fetchedData, ?array $existingData): bool
    {
        if (!$existingData || !isset($existingData['description'])) {
            $this->logger->info('Description does not exist, update required');
            return true;
        }

        if (!$fetchedData) {
            $this->logger->info('No fetched data, cant perform openAi enrichment');
            throw new \Exception('No fetched data, cant perform openAi enrichment');
        }

        $wikipediaLastUpdate = $fetchedData['wikipedia']['lastModified'] ?? '';
        $existingWikipediaLastUpdate = $existingData['last_update_wikipedia'] ?? '';

        if ($existingWikipediaLastUpdate == '') {
            $this->logger->info('No last update found, update required');
            return true;
        }

        if (Carbon::parse($wikipediaLastUpdate)->gt(Carbon::parse($existingWikipediaLastUpdate))) {
            $this->logger->info('Description outdated, update required');
            return true;
        }

        $this->logger->info('Description up to date, not sending openAI request');
        return false;
    }

    protected function fetchDataFromWiki(array $tags): array
    {
        $this->logger->info('Fetching Wikipedia data');
        if (isset($tags['wikipedia'])) {
            try {
                $wikipediaData = $this->wikipediaFetcher->fetchData($tags['wikipedia'] ?? null);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $wikipediaData = null;
            }
        } else {
            throw new \Exception('wikipedia tag not found');
        }

        $this->logger->info('Fetching Wikidata data');
        if (isset($tags['wikidata'])) {
            try {
                $wikidataData = $this->wikidataFetcher->fetchData($tags['wikidata'] ?? null);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $wikidataData = null;
            }
        } else {
            throw new \Exception('wikidata tag not found');
        }

        return [
            'wikipedia' => $wikipediaData,
            'wikidata' => $wikidataData,
        ];
    }
}