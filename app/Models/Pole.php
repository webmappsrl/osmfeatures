<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pole extends Model
{
    use HasFactory, OsmTagsProcessor, OsmFeaturesIdProcessor;

    protected $table = 'poles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'osm_id',
        'name',
        'osm_type',
        'admin_level',
        'tags',
        'geom',
        'updated_at',
    ];
}
