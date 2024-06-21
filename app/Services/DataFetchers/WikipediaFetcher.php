<?php

namespace App\Services\DataFetchers;

use App\Services\Contracts\DataFetcherInterface;
use Illuminate\Support\Facades\Http;

class WikipediaFetcher implements DataFetcherInterface
{
    public function fetchData(array $wikipediaTags): ?array
    {
        if (!$wikipediaTag) {
            Log::info('No Wikipedia tag provided');
            throw new \Exception('No Wikipedia tag provided');
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
        } else {
            Log::info('Failed to fetch data from Wikipedia, from link ' . $url);
            throw new \Exception('Failed to fetch data from Wikipedia, from link ' . $url);
        }
    }
}
