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

    /**
     * Get the wiki links in an html string
     * @return string
     */
    public function getWikiLinks(): string
    {
        $links = ['<div style="display:flex; justify-content: center;">'];

        if ($this->getWikidata()) {
            $links[] = '<a style="padding:5px;" href="https://www.wikidata.org/wiki/' . $this->getWikidata() . '" target="_blank"><img style=" border:1px solid gray; border-radius: 20% 20%; height: 45px; width: auto; padding:5px;" src="/images/Wikidata-logo.png" /></a>';
        }

        if ($this->getWikimediaCommons()) {
            $links[] = '<a style="padding:5px;" href="https://commons.wikimedia.org/wiki/' . $this->getWikimediaCommons() . '" target="_blank"><img style=" border:1px solid gray; border-radius: 20% 20%; height: 45px; width: auto; padding:5px;" src="/images/Wikimedia-logo.png" /></a>';
        }

        if ($this->getWikipedia()) {
            $links[] = '<a style="padding:5px;" href="https://en.wikipedia.org/wiki/' . $this->getWikipedia() . '" target="_blank"><img style=" border:1px solid gray; border-radius: 20% 20%; height: 45px; width: auto; padding:5px;" src="/images/Wikipedia-logo.jpeg" /></a>';
        }

        $links[] = '</div>';

        return implode('', $links);
    }
}