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
        $json = [];
        $this->logger->info('Enriching model: ' . get_class($model) . ' ' . $model->id);

        try {
            $json = array_merge($json, $this->enrichText($model));
        } catch (Exception $e) {
            $this->logger->error('Failed to enrich Text model ' . get_class($model) . ' with osmid ' . $model->osm_id . ': ' . $e->getMessage());
        }
        try {
            $json = array_merge($json, $this->enrichMedia($model));
        } catch (Exception $e) {
            $this->logger->error('Failed to enrich media model ' . get_class($model) . ' with osmid ' . $model->osm_id . ': ' . $e->getMessage());
        }

        Enrichment::updateOrCreate([
            'enrichable_osmfeatures_id' => $model::getOsmfeaturesId(),
        ], [
            'enrichable_id' => $model->id,
            'enrichable_type' => get_class($model),
            'data' => json_encode($json),
        ]);
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
            throw new Exception('No fetched data, cant perform openAi enrichment');
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

    protected function enrichText($model)
    {
        $json = [];
        $wikipediaLastUpdate = null;
        $wikidataLastUpdate = null;

        $tags = json_decode($model->tags, true);
        if (!isset($tags['wikipedia']) && !isset($tags['wikidata'])) {
            $this->logger->info('No wikipedia or wikidata tag found, skipping text enrichment.');
            return [];
        }

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
            try {
                $openAIdescription = $this->OpenAiGenerator->generateDescription($fetchedData, 1800);
                $openAIdescriptionEn = $this->OpenAiGenerator->translateTo('english', $openAIdescription);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                throw $e;
            }
            $this->logger->info('Description generated');
            try {
                $openAIabstract = $this->OpenAiGenerator->generateAbstractFromDescription($openAIdescription, 255);
                $openAIabstractEn = $this->OpenAiGenerator->translateTo('english', $openAIabstract);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                throw $e;
            }
            $this->logger->info('Abstract generated');


            if (isset($fetchedData['wikipedia']['lastModified'])) {
                $wikipediaLastUpdate = $fetchedData['wikipedia']['lastModified'];
            }
            if (isset($fetchedData['wikidata']['lastModified'])) {
                $wikidataLastUpdate = $fetchedData['wikidata']['lastModified'];
            }

            $json['last_update_wikipedia'] = $wikipediaLastUpdate;
            $json['last_update_wikidata'] = $wikidataLastUpdate;
        } else {
            $json['last_update_wikipedia'] = $existingData['last_update_wikipedia'] ?? null;
            $json['last_update_wikidata'] = $existingData['last_update_wikidata'] ?? null;
        }

        $json['abstract'] = [
            'it' => $shouldUpdateDescription ? $openAIabstract : ($existingData['abstract']['it'] ?? ''),
            'en' => $shouldUpdateDescription ? $openAIabstractEn : ($existingData['abstract']['en'] ?? ''),
        ];
        $json['description'] = [
            'it' => $shouldUpdateDescription ? $openAIdescription : ($existingData['description']['it'] ?? ''),
            'en' => $shouldUpdateDescription ? $openAIdescriptionEn : ($existingData['description']['en'] ?? ''),
        ];

        return $json;
    }

    protected function enrichMedia($model)
    {
        $json = [];
        $this->logger->info('Fetching images...');
        try {
            $imageData = $this->wikimediaService->fetchImages($model);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $imageData = null;
        }
        $this->logger->info('Images fetched');

        $json['images'] = $imageData;
        return $json;
    }
    protected function fetchDataFromWiki(array $tags): array
    {
        $json = [];
        $this->logger->info('Fetching Wikipedia data');

        if (isset($tags['wikipedia'])) {
            $json = array_merge($json, $this->fetchData('wikipedia', $tags['wikipedia']));
        }
        if (isset($tags['wikidata'])) {
            $json = array_merge($json, $this->fetchData('wikidata', $tags['wikidata']));
        }

        return $json;
    }

    protected function fetchData($tag, $value)
    {
        $json = [];
        if (isset($tag)) {
            try {
                $data = $this->{$tag . 'Fetcher'}->fetchData($value);
                $json[$tag] = $data;
                $this->logger->info('Fetching ' . $tag . ' data');
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $json;
    }
}
