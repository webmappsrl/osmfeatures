<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrichment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrichable_type',
        'enrichable_id',
        'update',
        'update_wikipedia',
        'update_wikidata',
        'update_wikicommons',
        'abstract',
        'description',
        'images'
    ];

    protected $casts = [
        'abstract' => 'array',
        'description' => 'array',
        'images' => 'array',
    ];

    public function enrichable()
    {
        return $this->morphTo();
    }
}