<?php

namespace App\Services\DataFetchers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Contracts\DataFetcherInterface;

class WikidataFetcher implements DataFetcherInterface
{
    public function fetchData(string $wikidataTag): ?array
    {
        if (!$wikidataTag) {
            Log::info('No Wikidata tag provided');
            throw new \Exception('No Wikidata tag provided');
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
        } else {
            Log::info('Failed to fetch Wikidata data from link: ' . $url);
            throw new \Exception('Failed to fetch Wikidata data from link: ' . $url);
        }
    }
}