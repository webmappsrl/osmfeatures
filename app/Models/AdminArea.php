<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminArea extends Pivot
{
    use HasFactory, OsmTagsProcessor;
    protected $table = 'admin_areas';

    protected $primaryKey = 'osm_id'; //set the primary key to osm_id because we do not have an id column

    protected $fillable = [
        'osm_id',
        'osm_type',
        'updated_at',
        'name',
        'tags',
        'admin_level',
        'geom',
    ];

    /**
     * Get the wikidata from tags column if it existsq
     */
    public function getWikidata(): ?string
    {
        $tags = json_decode($this->tags, true);

        return $tags['wikidata'] ?? null;
    }
}
