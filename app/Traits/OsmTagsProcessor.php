<?php

namespace App\Traits;

trait OsmTagsProcessor
{
    /**
     * Get the wikidata from tags column if it exists
     * @return string|null
     */
    public function getWikidataUrl(): ?string
    {
        $tags = json_decode($this->tags, true);

        $wikidata = $tags['wikidata'] ?? null;

        if ($wikidata) {
            return 'https://www.wikidata.org/wiki/' . $wikidata;
        }

        return null;
    }

    /**
     * Get the wikimedia commons from tags column if it exists
     * @return string|null
     */
    public function getWikimediaCommonsUrl(): ?string
    {
        $tags = json_decode($this->tags, true);

        $wikimediaCommons = $tags['wikimedia_commons'] ?? null;

        if ($wikimediaCommons) {
            return 'https://commons.wikimedia.org/wiki/' . $wikimediaCommons;
        }

        return null;
    }

    /**
     * Get the wikipedia from tags column if it exists
     * @return string|null
     */
    public function getWikipediaUrl(): ?string
    {
        $tags = json_decode($this->tags, true);

        $wikipedia = $tags['wikipedia'] ?? null;

        if ($wikipedia) {
            return 'https://en.wikipedia.org/wiki/' . $wikipedia;
        }

        return null;
    }

    /**
     * Get the wiki links in an html string
     * @return string
     */
    public function getWikiLinksAsHtml(): string
    {
        $links = ['<div style="display:flex; justify-content:center; text-align: center;">'];

        if ($this->getWikidataUrl()) {
            $links[] = '<a style="padding:5px;" href="https://www.wikidata.org/wiki/' . $this->getWikidataUrl() . '" target="_blank"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 28" stroke-width="1.5" stroke="orange" class="w-6 h-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
</svg></a>';
        }

        if ($this->getWikimediaCommonsUrl()) {
            $links[] = '<a style="padding:5px;" href="https://commons.wikimedia.org/wiki/' . $this->getWikimediaCommonsUrl() . '" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 28" stroke-width="1.5" stroke="green" class="w-6 h-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
</svg>
</a>';
        }

        if ($this->getWikipediaUrl()) {
            $links[] = '<a style="padding:5px;" href="https://en.wikipedia.org/wiki/' . $this->getWikipediaUrl() . '" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 28 28" stroke-width="1.5" stroke="blue" class="w-6 h-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l1.068.89c.442.369.535 1.01.216 1.49l-.51.766a2.25 2.25 0 0 1-1.161.886l-.143.048a1.107 1.107 0 0 0-.57 1.664c.369.555.169 1.307-.427 1.605L9 13.125l.423 1.059a.956.956 0 0 1-1.652.928l-.679-.906a1.125 1.125 0 0 0-1.906.172L4.5 15.75l-.612.153M12.75 3.031a9 9 0 0 0-8.862 12.872M12.75 3.031a9 9 0 0 1 6.69 14.036m0 0-.177-.529A2.25 2.25 0 0 0 17.128 15H16.5l-.324-.324a1.453 1.453 0 0 0-2.328.377l-.036.073a1.586 1.586 0 0 1-.982.816l-.99.282c-.55.157-.894.702-.8 1.267l.073.438c.08.474.49.821.97.821.846 0 1.598.542 1.865 1.345l.215.643m5.276-3.67a9.012 9.012 0 0 1-5.276 3.67m0 0a9 9 0 0 1-10.275-4.835M15.75 9c0 .896-.393 1.7-1.016 2.25" />
</svg>
</a>';
        }

        $links[] = '</div>';

        return implode('', $links);
    }

    /**
     * Get the osm url
     * @return string
     */
    public function getOsmUrl(): string
    {
        match ($this->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        return "https://www.openstreetmap.org/$osmType/$this->osm_id";
    }

    /**
     * Get the osm api url
     * @return string
     */
    public function getOsmApiUrl(): string
    {
        match ($this->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        return "https://www.openstreetmap.org/api/0.6/$osmType/$this->osm_id.json";
    }
}
