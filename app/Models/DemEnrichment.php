<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemEnrichment extends Enrichment
{
    use HasFactory;

    protected $fillable = [
        'data',
        'dem-enrichable_id',
        'dem-enrichable_type',
        'enrichable_osmfeatures_id',
    ];

    public function demEnrichable()
    {
        return $this->morphTo('dem-enrichable', 'dem-enrichable_type', 'dem-enrichable_id');
    }
}
