<?php

namespace App\Services\DataFetchers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Contracts\DataFetcherInterface;

class WikipediaFetcher implements DataFetcherInterface
{
    public function fetchData(string $wikipediaTag): ?array
    {
        if (!$wikipediaTag) {
            Log::info('Wikipedia tag is empty, returning null');
            return null;
        }

        $parts = explode(':', $wikipediaTag);
        $language = $parts[0];
        $title = $parts[1];

        $url = "https://{$language}.wikipedia.org/api/rest_v1/page/summary/" . rawurlencode($title);
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            $revisionId = intval($data['revision']);
            $lastModified = $data['timestamp'];
            $image['source_url'] = $data['originalimage']['source'] ?? null;
            $image['thumb_url'] = $data['thumbnail']['source'] ?? null;
            return [
                'title' => $data['title'],
                'content' => $data['extract'],
                'lastRevisionId' => $revisionId,
                'lastModified' => $lastModified,
                'image' => $image,
            ];
        } else {
            Log::info('Failed to fetch data from Wikipedia, from link ' . $url);
            throw new \Exception('Failed to fetch data from Wikipedia, from link ' . $url);
        }
    }
}
