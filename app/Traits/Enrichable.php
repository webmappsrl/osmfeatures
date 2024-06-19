<?php

namespace App\Traits;

use App\Models\Enrichment;

trait Enrichable
{
    public function enrichment()
    {
        return $this->morphOne(Enrichment::class, 'enrichable');
    }
}