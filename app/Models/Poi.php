<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poi extends Model
{
    use HasFactory;

    protected $table = 'pois';

    protected $fillable = ['osm_id', 'name', 'class', 'subclass', 'geom', 'updated_at'];

    protected $primaryKey = 'osm_id'; //set the primary key to osm_id because we do not have an id column


    /**
     * Get the wikidata from tags column if it existsq
     */
    public function getWikidata(): ?string
    {
        $tags = json_decode($this->tags, true);
        return $tags['wikidata'] ?? null;
    }
}
