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
    /**
     * OpenAI client
     *
     * @var \OpenAI
     */
    protected $openai;

    /**
     * Wikimedia service
     *
     * @var \App\Services\WikimediaService
     */
    protected $wikimediaService;

    /**
     * OpenAI model
     *
     * @var string
     */
    protected $openaiModel;

    /**
     * EnrichmentService constructor.
     *
     * @param \App\Services\WikimediaService $wikimediaService Wikimedia service
     */
    public function __construct(WikimediaService $wikimediaService)
    {
        $this->openai = OpenAI::client(env('OPENAI_API_KEY'));
        $this->wikimediaService = $wikimediaService;
        $this->openaiModel = config('openai.model');
    }

    /**
     * Enrich a model with OpenAI
     *
     * @param \Illuminate\Database\Eloquent\Model $model Model to enrich
     * @return void
     * @throws \Exception
     */
    public function enrich(Model $model)
    {
        Log::info('Enriching model: ' . get_class($model) . ' ' . $model->id);

        $tags = json_decode($model->tags, true);
        try {
            // Fetch existing enrichment
            $existingEnrichment = Enrichment::where('enrichable_id', $model->id)
                ->where('enrichable_type', get_class($model))
                ->first();

            $existingData = $existingEnrichment ? json_decode($existingEnrichment->data, true) : null;

            // Fetch data
            $data = $this->fetchDataFromTags($tags);

            // Check if we need to update the enrichment by comparing the last revision id from the api response with the existing one
            $shouldUpdateDescription = $this->shouldUpdateDescription($data, $existingData);

            if ($shouldUpdateDescription) {
                Log::info('Updating description');
                // Generate description
                $openAIdescription = $this->generateDescription($data);
                // Translate description to English
                $openAIdescriptionEn = $this->translateToEnglish($openAIdescription);
                Log::info('Updating abstract');

                // Generate abstract from description
                $openAIabstract = $this->generateAbstract($description);
                // Translate abstract to English
                $openAIabstractEn = $this->translateToEnglish($abstract);
                //get the last revision id from the wikipedia and wikidata api
                $wikipediaLastRevisionId = $data['wikipedia']['lastRevisionId'] ?? null;
                $wikidataLastRevisionId = $data['wikidata']['lastRevisionId'] ?? null;
            }

            if ($shouldUpdateDescription) {
                // Fetch images
                $imageData = $this->wikimediaService->fetchAndUploadImages($model);

                // Construct the final JSON
                $json = [
                    'last_revision_wikipedia' => $updatedAtWikipedia,
                    'last_revision_wikidata' => $data['wikidata']['updatedAt'],
                    'last_revision_wikicommons' => $updatedAtWikimediaCommons,
                    'abstract' => [
                        'it' => $openAIabstract,
                        'en' => $openAIabstractEn
                    ],
                    'description' => [
                        'it' => $openAIdescription,
                        'en' => $openAIdescriptionEn
                    ],
                    'images' => $imageData['urls'],
                ];

                Enrichment::updateOrCreate([
                    'enrichable_id' => $model->id,
                ], [
                    'enrichable_type' => get_class($model),
                    'data' => json_encode($json),
                ]);
            } else {
                Log::info('Enrichment already up to date');
                return;
            }
        } catch (Exception $e) {
            Log::error('Enrichment failed: ' . $e->getMessage());
            throw new \Exception('Failed to enrich model: ' . $e->getMessage());
        }
    }

    /**
     * Determine if the description should be updated based on last revision id.
     *
     * @param array $data Fetched data from tags
     * @param array|null $existingData Existing data in the enrichment
     * @return bool
     */
    protected function shouldUpdateDescription(array $data, ?array $existingData): bool
    {
        if (!$existingData || !isset($existingData['description'])) {
            Log::info('Description does not exist, update required');
            return true;
        }

        $wikipediaLastRevision = $data['wikipedia']['lastRevisionId'] ?? '';
        $existingWikipediaLastRevision = $existingData['last_revision_wikipedia'] ?? '';

        if ($wikipediaLastRevision != ($existingWikipediaLastRevision)) {
            Log::info('Last revision id is different, update required');
            return true;
        }

        Log::info('Description up to date');
        return false;
    }

    /**
     * Generate a description from tags
     *
     * @param array $tags Tags for the model
     * @return string Generated description 
     */
    protected function generateDescription(array $data): string
    {
        Log::info('Generating description prompt');
        $prompt = $this->generateDescriptionPrompt($data);

        Log::info('Generating OpenAI response for description');
        $response = $this->getOpenAIResponse($prompt, 3000);
        Log::info('Successfully generated OpenAI response for description');
        return $response['choices'][0]['message']['content'];
    }

    /**
     * Generate an abstract from a description
     *
     * @param string $description Description to generate abstract from
     * @return string Generated abstract
     */
    protected function generateAbstract(string $description): string
    {
        Log::info('Generating abstract prompt');
        $prompt = "Crea un abstract che sia lungo fino a 255 caratteri per la seguente descrizione:\n\n$description";
        Log::info('Generating OpenAI response for abstract');
        $response = $this->getOpenAIResponse($prompt, 300);
        Log::info('Successfully generated OpenAI response for abstract');
        return $response['choices'][0]['message']['content'];
    }

    /**
     * Translate text to English
     *
     * @param string $text Text to translate
     * @return string Translated text
     */
    protected function translateToEnglish(string $text): string
    {
        Log::info('Generating translation prompt');
        $prompt = "Traduci il seguente testo in inglese:\n\n$text";
        Log::info('Generating OpenAI response for translation');
        $response = $this->getOpenAIResponse($prompt, 1500);
        Log::info('Successfully generated OpenAI response for translation');
        return $response['choices'][0]['message']['content'];
    }

    /**
     * Fetch data from tags
     *
     * @param array $tags Tags for the model
     * @return array Data fetched from tags
     */
    protected function fetchDataFromTags(array $tags): array
    {
        Log::info('Fetching Wikipedia data');
        $wikipediaData = $this->fetchWikipediaData($tags['wikipedia'] ?? null);
        Log::info('Fetching Wikidata data');
        $wikidataData = $this->fetchWikidataData($tags['wikidata'] ?? null);
        Log::info('Successfully fetched data from tags');
        return ['wikipedia' => $wikipediaData, 'wikidata' => $wikidataData];
    }

    /**
     * Fetches data from Wikipedia for a given Wikipedia tag.
     *
     * @param string|null $wikipediaTag The Wikipedia tag to fetch data for.
     * @return array The fetched data, including the title and content of the Wikipedia page.
     */
    protected function fetchWikipediaData(?string $wikipediaTag): array
    {
        // If no Wikipedia tag is provided, return an empty array.
        if (!$wikipediaTag) {
            Log::info('No Wikipedia tag provided');
            return [];
        }

        // Split the Wikipedia tag into language and title.
        $parts = explode(':', $wikipediaTag);
        $language = $parts[0];
        $title = $parts[1];

        // Build the URL for the Wikipedia API.
        $url = "https://{$language}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($title);
        $response = Http::get($url);

        // If the response is successful, extract data from the JSON.
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

        // If the response is not successful, return an empty array.
        Log::info('Failed to fetch data from Wikipedia, returning empty array');
        return ['title' => '', 'content' => '', 'lastRevision' => '', 'lastModified' => ''];
    }

    /**
     * Fetches data from Wikidata for a given Wikidata tag.
     *
     * @param string|null $wikidataTag The Wikidata tag to fetch data for.
     * @return array The fetched data, including the title and content of the Wikidata entity.
     */
    protected function fetchWikidataData(?string $wikidataTag): array
    {
        // If no Wikidata tag is provided, return an empty array.
        if (!$wikidataTag) {
            Log::info('No Wikidata tag provided');
            return [];
        }

        // Build the URL for the Wikidata API.
        $url = "https://www.wikidata.org/wiki/Special:EntityData/{$wikidataTag}.json";
        $response = Http::get($url);

        // If the response is successful, extract data from the JSON.
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

        // If the response is not successful, return an empty array.
        Log::info('Failed to fetch Wikidata data, returning empty array');
        return ['title' => '', 'content' => '', 'lastRevision' => '', 'lastModified' => ''];
    }


    /**
     * Generates a description prompt based on the given data.
     *
     * @param array $data The data used to generate the prompt.
     * @return string The generated description prompt.
     */
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

    /**
     * Generates an OpenAI response based on the given prompt and maximum tokens.
     *
     * @param string $prompt The prompt to generate the response for.
     * @param int $maxTokens The maximum number of tokens to generate.
     * @return OpenAI\Responses\Chat\CreateResponse The generated OpenAI response.
     * @throws \Exception If the OpenAI response generation fails.
     */
    protected function getOpenAIResponse(string $prompt, int $maxTokens): OpenAI\Responses\Chat\CreateResponse
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
