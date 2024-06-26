<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrichment extends Model
{
    use HasFactory;

    protected $fillable = [
        'data',
        'enrichable_id',
        'enrichable_type',
        'enrichable_osmfeatures_id',
    ];

    public function enrichable()
    {
        return $this->morphTo();
    }
}
