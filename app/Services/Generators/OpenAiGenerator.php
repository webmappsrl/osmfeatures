<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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
        $this->openai = OpenAI::client(env('OPENAI_API_KEY'));
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
        $featureTitle = $data['wikipedia']['title'] ?? $data['wikidata']['title'] ?? '';
        $content = $data['wikipedia']['content'] . "\n\n" . $data['wikidata']['content']  ?? '';

        if (!$featureTitle && !$content) {
            $this->logger->error('No feature title and content provided');
            throw new \Exception('No feature title and content from wikipedia and wikidata provided');
        }
        if (!$featureTitle) {
            $this->logger->info('No feature title provided');
        }

        if (!$content) {
            $this->logger->info('No content provided');
        }

        // Prepare the prompt for the GPT model.
        $prompt = "Crea una descrizione lunga $length caratteri riguardo la feature openstreetmap $featureTitle con il contenuto seguente: $content. Aggiungi informazioni in base alle tue conoscenzen sulla feature per raggiungere la quota di caratteri stabilita.";

        // Send the prompt to the GPT model and get the response.
        return $this->getOpenAiResponse($prompt, 500);
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
            throw new \Exception('No description provided');
        }
        // Prepare the prompt for the GPT model.
        $prompt = "Crea un abstract di $length caratteri a partire dalla descrizione seguente: $description.";

        // Send the prompt to the GPT model and get the response.
        return $this->getOpenAiResponse($prompt, 100);
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
            $this->logger->error('No text provided for translation');
            throw new \Exception('No text provided for translation');
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
     * @return string|null The response from the GPT model or null if an error occurs.
     */
    private function getOpenAiResponse(string $prompt, int $maxTokens): ?string
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => $this->openaiModel,
                'prompt' => $prompt,
                'max_tokens' => $maxTokens,
            ]);
            return $response['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
