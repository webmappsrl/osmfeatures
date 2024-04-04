<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdminArea extends Pivot
{
    use HasFactory, OsmTagsProcessor;

    protected $table = 'admin_areas';

    protected $primaryKey = 'id'; //set the primary key to osm_id because we do not have an id column

    protected $fillable = [
        'osm_id',
        'osm_type',
        'updated_at',
        'name',
        'tags',
        'admin_level',
        'geom',
    ];
}
