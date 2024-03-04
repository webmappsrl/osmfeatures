<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pole extends Model
{
    use HasFactory;

    protected $table = 'poles';

    protected $primaryKey = 'osm_id'; //set the primary key to osm_id because we do not have an id column

    protected $fillable = [
        'osm_id',
        'name',
        'osm_type',
        'admin_level',
        'tags',
        'geom',
        'updated_at',
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
