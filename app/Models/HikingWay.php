<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HikingWay extends Model
{
    use HasFactory, OsmTagsProcessor;

    protected $table = 'hiking_ways';

    protected $primaryKey = 'osm_id';
}
