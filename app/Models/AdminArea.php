<?php

namespace App\Models;

use App\Traits\OsmFeaturesIdProcessor;
use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdminArea extends Pivot
{
    use HasFactory, OsmTagsProcessor, OsmFeaturesIdProcessor;

    protected $table = 'admin_areas';

    protected $primaryKey = 'id';

    protected $fillable = [
        'osm_id',
        'osm_type',
        'updated_at',
        'name',
        'tags',
        'admin_level',
        'geom',
    ];

    protected $casts = [
        'admin_level ' => 'integer',
    ];
}
