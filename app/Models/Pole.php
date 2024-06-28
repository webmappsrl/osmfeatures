<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmFeaturesIdProcessor;
use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pole extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

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
