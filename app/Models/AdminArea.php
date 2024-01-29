<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AdminArea extends Pivot
{
    protected $table = 'admin_areas';

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
