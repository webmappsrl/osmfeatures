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
            //Fetch existing enrichment
            $existingEnrichment = Enrichment::where('enrichable_id', $model->id)
                ->where('enrichable_type', get_class($model))
                ->first();

            $existingData = $existingEnrichment ? json_decode($existingEnrichment->data, true) : null;

            //Fetch data
            $data = $this->fetchDataFromTags($tags);

            //Check if we need to update the enrichment
            $shouldUpdate = $this->shouldUpdateEnrichment($data, $existingData);

            if ($shouldUpdate) {
                Log::info('Updating enrichment');
                //Generate description
                $descriptionData = $this->generateDescription($tags);
                $description = $descriptionData['response'];
                $updatedAtWikipedia = $descriptionData['updatedAt'];

                //Generate abstract from description
                $abstract = $this->generateAbstract($description);

                //Translate description and abstract to English
                $descriptionEn = $this->translateToEnglish($description);
                $abstractEn = $this->translateToEnglish($abstract);

                //Fetch images
                $imageUrls = $this->fetchImages($model);
                $updatedAtWikimediaCommons = Carbon::now()->toIso8601String();

                // Construct the final JSON
                $json = [
                    'update_wikipedia' => $updatedAtWikipedia,
                    'update_wikidata' => $data['wikidata']['updatedAt'],
                    'update_wikicommons' => $updatedAtWikimediaCommons,
                    'abstract' => [
                        'it' => $abstract,
                        'en' => $abstractEn
                    ],
                    'description' => [
                        'it' => $description,
                        'en' => $descriptionEn
                    ],
                    'images' => $imageUrls,
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
     * Determine if the enrichment should be updated based on updated_at fields.
     *
     * @param array $data Fetched data from tags
     * @param array|null $existingData Existing data in the enrichment
     * @return bool
     */
    protected function shouldUpdateEnrichment(array $data, ?array $existingData): bool
    {
        if (!$existingData) {
            Log::info('Enrichment does not exist, update required');
            return true;
        }

        $wikipediaUpdatedAt = new Carbon($data['wikipedia']['updatedAt']);
        $wikidataUpdatedAt = new Carbon($data['wikidata']['updatedAt']);
        $existingWikipediaUpdatedAt = new Carbon($existingData['update_wikipedia'] ?? '1970-01-01');
        $existingWikidataUpdatedAt = new Carbon($existingData['update_wikidata'] ?? '1970-01-01');

        if ($wikipediaUpdatedAt->gt($existingWikipediaUpdatedAt) || $wikidataUpdatedAt->gt($existingWikidataUpdatedAt)) {
            Log::info('Enrichment outdated, update required');
            return true;
        }

        Log::info('Enrichment up to date');
        return false;
    }

    /**
     * Generate a description from tags
     *
     * @param array $tags Tags for the model
     * @return array Generated description and updated date
     */
    protected function generateDescription(array $tags): array
    {
        Log::info('Fetching data from tags');
        $data = $this->fetchDataFromTags($tags);
        Log::info('Generating description prompt');
        $prompt = $this->generateDescriptionPrompt($data);

        Log::info('Generating OpenAI response');
        $response = $this->getOpenAIResponse($prompt, 3000);
        Log::info('Successfully generated OpenAI response');
        return ['response' => $response['choices'][0]['message']['content'], 'updatedAt' => $data['wikipedia']['updatedAt']];
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
            return ['title' => '', 'content' => ''];
        }

        // Split the Wikipedia tag into language and title.
        $parts = explode(':', $wikipediaTag);
        $language = $parts[0];
        $title = $parts[1];

        // Build the URL for the Wikipedia API.
        $url = "https://{$language}.wikipedia.org/api/rest_v1/page/summary/" . urlencode($title);
        $response = Http::get($url);

        // If the response is successful, extract the title and content from the JSON data.
        if ($response->successful()) {
            $data = $response->json();
            $updatedAt = $response->header('Last-Modified');
            //parse updated date string to iso8601
            $updatedAt = Carbon::parse($updatedAt)->toIso8601String();
            return [
                'title' => $data['title'],
                'content' => $data['extract'],
                'updatedAt' => $updatedAt
            ];
        }

        // If the response is not successful, return an empty array.
        return ['title' => '', 'content' => ''];
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
            return ['title' => '', 'content' => ''];
        }

        // Build the URL for the Wikidata API.
        $url = "https://www.wikidata.org/wiki/Special:EntityData/{$wikidataTag}.json";
        $response = Http::get($url);

        // If the response is successful, extract the title and content from the JSON data.
        if ($response->successful()) {
            $data = $response->json();
            $updatedAt = $response->header('Last-Modified');
            //parse updated date string to iso8601
            $updatedAt = Carbon::parse($updatedAt)->toIso8601String();
            $entity = $data['entities'][$wikidataTag];
            $descriptions = $entity['descriptions'] ?? [];
            $description = $descriptions['en']['value'] ?? '';

            return [
                'title' => $entity['labels']['en']['value'] ?? '',
                'content' => $description,
                'updatedAt' => $updatedAt
            ];
        }

        // If the response is not successful, return an empty array.
        return ['title' => '', 'content' => ''];
    }

    /**
     * Fetches images for a given model.
     *
     * @param Model $model The model to fetch images for.
     * @return array The URLs of the fetched images.
     */
    protected function fetchImages(Model $model): array
    {
        // Decode the JSON tags of the model.
        $tags = json_decode($model->tags, true);
        $imageUrls = [];

        // If the model has a Wikimedia Commons tag, fetch and upload the image.
        if (isset($tags['wikimedia_commons'])) {
            $wikimediaFilename = str_replace('File:', '', $tags['wikimedia_commons']);
            $imageUrls = $this->wikimediaService->fetchAndUploadImages($wikimediaFilename, $tags['name'] ?? null);
        }

        // Return the URLs of the fetched images.
        return $imageUrls;
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
            Log::info('Generating OpenAI response');
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
            Log::info('Successfully generated OpenAI response');
            return $response;
        } catch (Exception $e) {
            Log::error('Error generating OpenAI response: ' . $e->getMessage());
            throw new \Exception('Failed to generate OpenAI response: ' . $e->getMessage());
        }
    }
}
