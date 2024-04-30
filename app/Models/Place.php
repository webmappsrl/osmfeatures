<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory, OsmTagsProcessor, OsmFeaturesIdProcessor;

    protected $table = 'places';

    protected $fillable = ['osm_id', 'name', 'class', 'subclass', 'geom', 'updated_at'];

    protected $primaryKey = 'id';
}
