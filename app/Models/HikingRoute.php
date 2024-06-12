<?php

namespace App\Models;

use App\Traits\OsmFeaturesIdProcessor;
use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HikingRoute extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;

    protected $table = 'hiking_routes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'updated_at',
    ];
}
