<?php

namespace App\Traits;

use App\Models\Enrichment;
use App\Models\DemEnrichment;

trait Enrichable
{
    public function enrichment()
    {
        return $this->morphOne(Enrichment::class, 'enrichable');
    }

    public function demEnrichment()
    {
        return $this->morphOne(DemEnrichment::class, 'dem-enrichable');
    }
}
