<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAreasEnrichment extends Enrichment
{
    use HasFactory;
    protected $fillable = [
        'data',
        'admin_areas-enrichable_id',
        'admin_areas-enrichable_type',
        'enrichable_osmfeatures_id',
    ];

    public function adminAreasEnrichable()
    {
        return $this->morphTo('admin_areas-enrichable', 'admin_areas-enrichable_type', 'admin_areas-enrichable_id');
    }
}
