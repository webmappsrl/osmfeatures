<?php

namespace App\Models;

use App\Traits\OsmTagsProcessor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HikingWay extends Model
{
    use HasFactory, OsmTagsProcessor;

    protected $table = 'hiking_ways';

    protected $primaryKey = 'osm_id';
}
