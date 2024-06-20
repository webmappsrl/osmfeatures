<?php

namespace App\Services;

use App\Models\Enrichment;
use Log;
use OpenAI;
use Exception;
use App\Services\WikimediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Service for enriching models with OpenAI
 */
class EnrichmentService
{
    protected $openai;
    protected $wikimediaService;
    protected $openaiModel;

    public function __construct(WikimediaService $wikimediaService)
    {
        $this->openai = OpenAI::client(env('OPENAI_API_KEY'));
        $this->wikimediaService = $wikimediaService;
        $this->openaiModel = config('openai.model');
    }

    public function enrich(Model $model)
    {
        Log::info('Enriching model: ' . get_class($model) . ' ' . $model->id);

        $tags = json_decode($model->tags, true);
        try {
            $existingEnrichment = Enrichment::where('enrichable_id', $model->id)
                ->where('enrichable_type', get_class($model))
                ->first();

            $existingData = $existingEnrichment ? json_decode($existingEnrichment->data, true) : null;
            $data = $this->fetchDataFromWiki($tags);
            $shouldUpdateDescription = $this->shouldUpdateDescription($data, $existingData);

            if ($shouldUpdateDescription) {
                Log::info('Updating description');
                $openAIdescription = $this->generateDescription($data);
                $openAIdescriptionEn = $this->translateToEnglish($openAIdescription);
                Log::info('Updating abstract');
                $openAIabstract = $this->generateAbstract($openAIdescription);
                $openAIabstractEn = $this->translateToEnglish($openAIabstract);
                $wikipediaLastUpdate = $data['wikipedia']['lastModified'] ?? null;
                $wikidataLastUpdate = $data['wikidata']['lastModified'] ?? null;
            }

            // Always update images regardless of whether the description is updated
            $imageData = $this->wikimediaService->fetchAndUploadImages($model);

            // Construct the final JSON
            $json = [
                'last_update_wikipedia' => $wikipediaLastUpdate ?? ($existingData['last_update_wikipedia'] ?? null),
                'last_update_wikidata' => $wikidataLastUpdate ?? ($existingData['last_update_wikidata'] ?? null),
                'last_update_wikimedia_commons' => $imageData['lastWikiCommonsUpdate'] ?? null,
                'abstract' => [
                    'it' => $shouldUpdateDescription ? $openAIabstract : ($existingData['abstract']['it'] ?? ''),
                    'en' => $shouldUpdateDescription ? $openAIabstractEn : ($existingData['abstract']['en'] ?? ''),
                ],
                'description' => [
                    'it' => $shouldUpdateDescription ? $openAIdescription : ($existingData['description']['it'] ?? ''),
                    'en' => $shouldUpdateDescription ? $openAIdescriptionEn : ($existingData['description']['en'] ?? ''),
                ],
                'images' => $imageData['urls'],
            ];

            Enrichment::updateOrCreate([
                'enrichable_id' => $model->id,
            ], [
                'enrichable_type' => get_class($model),
                'data' => json_encode($json),
            ]);
        } catch (Exception $e) {
            Log::error('Enrichment failed: ' . $e->getMessage());
            throw new \Exception('Failed to enrich model: ' . $e->getMessage());
        }

        Log::info('Enrichment successful');
    }

    protected function shouldUpdateDescription(array $data, ?array $existingData): bool
    {
        if (!$existingData || !isset($existingData['description'])) {
            Log::info('Description does not exist, update required');
            return true;
        }

        $wikipediaLastUpdate = $data['wikipedia']['lastModified'] ?? '';
        $existingWikipediaLastUpdate = $existingData['last_update_wikipedia'] ?? '';

        if ($existingWikipediaLastUpdate == '') {
            Log::info('No last update found, starting a new update');
            return true;
        }

        if (Carbon::parse($wikipediaLastUpdate)->gt(Carbon::parse($existingWikipediaLastUpdate))) {
            Log::info('Description outdated, update required');
            return true;
        }

        Log::info('Description up to date');
        return false;
    }

    protected function generateDescription(array $data): string
    {
        Log::info('Generating description prompt');
        $prompt = $this->generateDescriptionPrompt($data);

        Log::info('Generating OpenAI response for description');
        $response = $this->getOpenAIResponse($prompt, 3000);
        Log::info('Successfully generated OpenAI response for description');
        return $response['choices'][0]['message']['content'];
    }

    protected function generateAbstract(string $description): string
    {
        Log::info('Generating abstract prompt');
        $prompt = "Crea un abstract che sia lungo fino a 255 caratteri per la seguente descrizione:\n\n$description";
        Log::info('Generating OpenAI response for abstract');
        $response = $this->getOpenAIResponse($prompt, 300);
        Log::info('Successfully generated OpenAI response for abstract');
        return $response['choices'][0]['message']['content'];
    }

    protected function translateToEnglish(string $text): string
    {
        Log::info('Generating translation prompt');
        $prompt = "Traduci il seguente testo in inglese:\n\n$text";
        Log::info('Generating OpenAI response for translation');
        $response = $this->getOpenAIResponse($prompt, 1500);
        Log::info('Successfully generated OpenAI response for translation');
        return $response['choices'][0]['message']['content'];
    }

    protected function fetchDataFromWiki(array $tags): array
    {
        Log::info('Fetching Wikipedia data');
        $wikipediaData = $this->fetchWikipediaData($tags['wikipedia'] ?? null);
        Log::info('Fetching Wikidata data');
        $wikidataData = $this->fetchWikidataData($tags['wikidata'] ?? null);
        Log::info('Successfully fetched data from tags');
        return ['wikipedia' => $wikipediaData, 'wikidata' => $wikidataData];
    }

    protected function fetchWikipediaData(?string $wikipediaTag): array
    {
        if (!$wikipediaTag) {
            Log::info('No Wikipedia tag provided');
            return [];
        }

        $parts = explode(':', $wikipediaTag);
        $language = $parts[0];
        $title = $parts[1];

        $url = "https://{$language}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($title);
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            $revisionId = intval($data['revision']);
            $lastModified = $data['timestamp'];
            return [
                'title' => $data['title'],
                'content' => $data['extract'],
                'lastRevisionId' => $revisionId,
                'lastModified' => $lastModified
            ];
        }

        Log::info('Failed to fetch data from Wikipedia, returning empty array');
        return ['title' => '', 'content' => '', 'lastRevision' => '', 'lastModified' => ''];
    }

    protected function fetchWikidataData(?string $wikidataTag): array
    {
        if (!$wikidataTag) {
            Log::info('No Wikidata tag provided');
            return [];
        }

        $url = "https://www.wikidata.org/wiki/Special:EntityData/{$wikidataTag}.json";
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            $entity = $data['entities'][$wikidataTag];
            $descriptions = $entity['descriptions'] ?? [];
            $description = $descriptions['en']['value'] ?? '';
            $revisionId = $entity['lastrevid'] ?? '';
            $lastModified = $entity['modified'] ?? '';

            return [
                'title' => $entity['labels']['en']['value'] ?? '',
                'content' => $description,
                'lastRevisionId' => $revisionId,
                'lastModified' => $lastModified,
            ];
        }

        Log::info('Failed to fetch Wikidata data, returning empty array');
        return ['title' => '', 'content' => '', 'lastRevision' => '', 'lastModified' => ''];
    }

    protected function generateDescriptionPrompt(array $data): string
    {
        Log::info('Generating description prompt');
        $combinedContent = $data['wikipedia']['content'] . "\n\n" . $data['wikidata']['content'];
        $title = $data['wikipedia']['title'] ?? $data['wikidata']['title'];

        $prompt = "Crea una descrizione lunga tra 1000 e 1800 caratteri riguardo la feature openstreetmap $title con il contenuto
seguente: $combinedContent. Aggiungi informazioni in base alle tue conoscenze per raggiungere la quota di caratteri
stabilita.";
        Log::info('Successfully generated description prompt');
        return $prompt;
    }

    protected function getOpenAIResponse(string $prompt, int $maxTokens)
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => $this->openaiModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an openstreetmap expert that provides accurate abstracts and descriptions for openstreetmap features.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $maxTokens,
            ]);
            return $response;
        } catch (Exception $e) {
            Log::error('Error generating OpenAI response: ' . $e->getMessage());
            throw new \Exception('Failed to generate OpenAI response: ' . $e->getMessage());
        }
    }
}
