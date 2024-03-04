<?php

namespace App\Traits;

trait OsmTagsProcessor
{
    /**
     * Get the wikidata from tags column if it exists
     * @return string|null
     */
    public function getWikidata(): ?string
    {
        $tags = json_decode($this->tags, true);

        return $tags['wikidata'] ?? null;
    }

    /**
     * Get the wikimedia commons from tags column if it exists
     * @return string|null
     */
    public function getWikimediaCommons(): ?string
    {
        $tags = json_decode($this->tags, true);

        return $tags['wikimedia_commons'] ?? null;
    }

    /**
     * Get the wikipedia from tags column if it exists
     * @return string|null
     */
    public function getWikipedia(): ?string
    {
        $tags = json_decode($this->tags, true);

        return $tags['wikipedia'] ?? null;
    }
}
