<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Models\OsmfeaturesModel;
use App\Traits\OsmTagsProcessor;
use Illuminate\Support\Facades\DB;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminArea extends OsmfeaturesPivot
{
    use HasFactory;

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
