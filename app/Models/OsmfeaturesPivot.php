<?php

namespace App\Models;

use App\Models\OsmfeaturesModel;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class OsmfeaturesPivot extends OsmfeaturesModel
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
