<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmFeaturesIdProcessor;
use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HikingRoute extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

    protected $table = 'hiking_routes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'updated_at',
        'has_invalid_geometry',
        'admin_areas'
    ];
}
