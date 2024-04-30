<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HikingRoute extends Model
{
    use HasFactory, OsmTagsProcessor, OsmFeaturesIdProcessor;

    protected $table = 'hiking_routes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'updated_at',
    ];
}
