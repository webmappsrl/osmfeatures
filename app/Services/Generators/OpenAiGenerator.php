<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\Log;

/**
 * Class OpenAIGenerator
 * @package App\Services\Generators
 *
 * This class generates descriptions and abstracts using OpenAI's model.
 */
class OpenAIGenerator
{
    /**
     * OpenAI client
     *
     * @var \OpenAI
     */
    protected $openAi;

    /**
     * OpenAI model
     *
     * @var string
     */
    protected $openaiModel;

    /**
     * Logger
     *
     * @var Log
     */
    protected $logger;


    public function __construct()
    {
        $this->openai = \OpenAI::client(config('openai.api_key'));
        $this->openaiModel = config('openai.model');
        $this->logger = Log::channel('openai');
    }

    /**
     * Generate a description about a given content.
     *
     * @param array $data data to use for the prompt
     * @param int $length The length of the description.
     * @return string|null The generated description or null if an error occurs.
     */
    public function generateDescription(array $data, int $length): ?string
    {
        if (!$data) {
            $this->logger->error('No data fetched from wikis provided to generate description');
            return null;
        }
        $featureTitle = $data['wikipedia']['title'] ?? $data['wikidata']['title'] ?? '';
        $content = '';

        $wikipediaContent = $data['wikipedia']['content'] ?? '';
        $wikidataContent = $data['wikidata']['content'] ?? '';

        if (!empty($wikipediaContent) && !empty($wikidataContent)) {
            $content = $wikipediaContent . ' ' . $wikidataContent;
        } elseif (!empty($wikipediaContent)) {
            $content = $wikipediaContent;
        } elseif (!empty($wikidataContent)) {
            $content = $wikidataContent;
        }

        if (!$featureTitle && !$content) {
            $this->logger->error('No feature title and content provided. Not enough elements to generate openAI prompt. Returning null');
            return null;
        }
        if (!$featureTitle) {
            $this->logger->info('No feature title provided, using only content for the openAI prompt');
        }

        if (!$content) {
            $this->logger->info('No content provided, using only feature title for the openAI prompt');
        }

        // Prepare the prompt for the GPT model dynamically.
        $promptParts = ["Crea una descrizione lunga $length caratteri in lingua italiana riguardo la feature openstreetmap"];

        if ($featureTitle) {
            $promptParts[] = $featureTitle;
        }

        if ($content) {
            $promptParts[] = "con il contenuto seguente: $content.";
        }

        $promptParts[] = "Aggiungi informazioni in base alle tue conoscenze sulla feature per raggiungere la quota di caratteri stabilita.";

        $prompt = implode(' ', $promptParts);

        // Send the prompt to the GPT model and get the response.
        return $this->getOpenAiResponse($prompt, 1000);
    }

    /**
     * Generate an abstract from a given description.
     *
     * @param string $description The description to base the abstract on.
     * @param int $length The length of the abstract.
     * @return string|null The generated abstract or null if an error occurs.
     */
    public function generateAbstractFromDescription(string $description, int $length): ?string
    {
        if (!$description) {
            $this->logger->error('No description provided');
            return null;
        }
        // Prepare the prompt for the GPT model.
        $prompt = "Crea un riassunto di $length caratteri massimo in lingua italiana della descrizione seguente: $description.";

        // Send the prompt to the GPT model and get the response.
        return $this->getOpenAiResponse($prompt, 400);
    }

    /**
     * Translate a given text to a specified language.
     *
     * @param string $language The language to translate the text to.
     * @param string $text The text to be translated.
     * @return string|null The translated text or null if an error occurs.
     */
    public function translateTo(string $language, string $text): ?string
    {
        // Check if the text to be translated is provided.
        if (!$text) {
            // Log an error and throw an exception if no text is provided.
            $this->logger->error('No text provided for translation, returning null');
            return null;
        }

        $this->logger->info('Generating translation prompt');

        // Prepare the translation prompt.
        $prompt = "Traduci il seguente testo in $language:\n\n$text";

        $this->logger->info('Generating OpenAI response for translation');

        // Send the translation prompt to the GPT model and get the response.
        return $this->getOpenAiResponse($prompt, 1500);
    }

    /**
     * Get the OpenAI response for a given prompt.
     *
     * @param string $prompt The prompt to send to the GPT model.
     * @param int $maxTokens The maximum number of tokens to generate in the response. 1 token = 4 characters
     * @return string|null The response from the GPT model
     */
    private function getOpenAiResponse(string $prompt, int $maxTokens): ?string
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => $this->openaiModel,
                'messages' => [
                    ['role' => 'user', 'content' => 'You are a geography expert specialized in providing helpful information about the provided localities.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
            ]);
            return $response['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
