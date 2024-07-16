<?php

namespace App\Services\DataFetchers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Contracts\DataFetcherInterface;

class WikiDataFetcher implements DataFetcherInterface
{
    public function fetchData(string $wikidataTag): ?array
    {
        if (!$wikidataTag) {
            Log::info('Wikidata tag is empty');
            return null;
        }

        $url = "https://www.wikidata.org/wiki/Special:EntityData/{$wikidataTag}.json";
        $response = Http::get($url);

        if ($response->status() === 200) {
            $data = $response->json();

            // Check if the response contains a different entity ID
            $newWikidataTag = array_keys($data['entities'])[0];
            if ($newWikidataTag !== $wikidataTag) {
                Log::info('Wikidata tag changed from ' . $wikidataTag . ' to ' . $newWikidataTag);
                $wikidataTag = $newWikidataTag;
            }

            return $this->extractData($data, $wikidataTag);
        } elseif ($response->status() === 301 || $response->status() === 302) {
            // Handle redirect
            $newUrl = $response->header('Location');
            Log::info('Redirected to ' . $newUrl);
            if ($newUrl) {
                $response = Http::get($newUrl);
                if ($response->successful()) {
                    $newWikidataTag = $this->extractWikidataTagFromUrl($newUrl);
                    return $this->extractData($response->json(), $newWikidataTag);
                }
            } else {
                Log::info('No new URL found in redirect');
                throw new \Exception('Failed to fetch Wikidata data from link: ' . $newUrl);
            }
        }

        Log::info('Failed to fetch Wikidata data from link: ' . $url);
        throw new \Exception('Failed to fetch Wikidata data from link: ' . $url);
    }

    private function extractData(array $data, string $wikidataTag): array
    {
        $entity = $data['entities'][$wikidataTag];
        $descriptions = $entity['descriptions'] ?? [];
        $description = $descriptions['en']['value'] ?? '';
        $revisionId = $entity['lastrevid'] ?? '';
        $lastModified = $entity['modified'] ?? '';
        $imageName = $entity['claims']['P18'][0]['mainsnak']['datavalue']['value'] ?? null;

        $image = [];

        if ($imageName) {
            // replace spaces with underscores
            $imageName = str_replace(' ', '_', $imageName);

            // Effettuare la richiesta API per ottenere le informazioni sull'immagine
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'titles' => 'File:' . $imageName,
                'format' => 'json',
                'prop' => 'imageinfo',
                'iiprop' => 'url|size|mime|metadata',
            ]);

            $responseData = $response->json();

            // Extract image url from response
            if (isset($responseData['query']['pages'])) {
                foreach ($responseData['query']['pages'] as $page) {
                    if (isset($page['imageinfo'][0])) {
                        $image['source_url'] = $page['imageinfo'][0]['url'] ?? null;
                    }
                }
            }
        }

        return [
            'title' => $entity['labels']['en']['value'] ?? '',
            'content' => $description,
            'lastRevisionId' => $revisionId,
            'lastModified' => $lastModified,
            'image' => $image,
        ];
    }


    public function extractWikidataTagFromUrl(string $url): string
    {
        preg_match('/Q\d+/', $url, $matches);
        return $matches[0] ?? '';
    }
}