<?php

namespace App\Services;

use App\Models\Enrichment;
use Log;
use OpenAI;
use Exception;
use App\Services\WikimediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

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
        $tags = json_decode($model->tags, true);
        try {
            //Generate description
            $description = $this->generateDescription($tags);

            //Generate abstract from description
            $abstract = $this->generateAbstract($description);

            //Translate description and abstract to English
            $descriptionEn = $this->translateToEnglish($description);
            $abstractEn = $this->translateToEnglish($abstract);

            //Fetch images
            $imageUrls = $this->fetchImages($model);

            // Construct the final JSON
            $json = [
                'abstract' => [
                    'it' => $abstract,
                    'en' => $abstractEn,
                ],
                'description' => [
                    'it' => $description,
                    'en' => $descriptionEn,
                ],
                'images' => $imageUrls,
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
    }

    /**
     * Generate a description from tags
     *
     * @param array $tags Tags for the model
     * @return string Generated description
     */
    protected function generateDescription(array $tags): string
    {
        $data = $this->fetchDataFromTags($tags);
        $prompt = $this->generateDescriptionPrompt($data);

        $response = $this->getOpenAIResponse($prompt, 3000);
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
        $prompt = "Crea un abstract che sia lungo fino a 255 caratteri per la seguente descrizione:\n\n$description";
        $response = $this->getOpenAIResponse($prompt, 300);
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
        $prompt = "Traduci il seguente testo in inglese:\n\n$text";
        $response = $this->getOpenAIResponse($prompt, 1500);
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
        $wikipediaData = $this->fetchWikipediaData($tags['wikipedia'] ?? null);
        $wikidataData = $this->fetchWikidataData($tags['wikidata'] ?? null);
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
            return [
                'title' => $data['title'],
                'content' => $data['extract'],
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
            $entity = $data['entities'][$wikidataTag];
            $descriptions = $entity['descriptions'] ?? [];
            $description = $descriptions['en']['value'] ?? '';

            return [
                'title' => $entity['labels']['en']['value'] ?? '',
                'content' => $description,
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
        // Combine the content of Wikipedia and Wikidata.
        $combinedContent = $data['wikipedia']['content'] . "\n\n" . $data['wikidata']['content'];
        $title = $data['wikipedia']['title'] ?? $data['wikidata']['title'];

        // Generate the description prompt.
        return "Crea una descrizione lunga tra 1000 e 1800 caratteri riguardo la feature openstreetmap $title con il contenuto
seguente: $combinedContent. Aggiungi informazioni in base alle tue conoscenze per raggiungere la quota di caratteri
stabilita.";
    }

    /**
     * Generates an OpenAI response based on the given prompt and maximum tokens.
     *
     * @param string $prompt The prompt to generate the response for.
     * @param int $maxTokens The maximum number of tokens to generate.
     * @return array The generated OpenAI response.
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
                        'content' => 'You are an openstreetmap expert that provides accurate abstracts and descriptions for openstreetmap
                                    features.',
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
