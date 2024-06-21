<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmFeaturesIdProcessor;
use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

    protected $table = 'places';

    protected $fillable = ['osm_id', 'name', 'class', 'subclass', 'geom', 'updated_at'];

    protected $primaryKey = 'id';
}