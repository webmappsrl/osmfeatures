<?php

namespace App\Traits;

use App\Models\AdminAreasEnrichment;
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

    public function adminAreasEnrichment()
    {
        return $this->morphOne(AdminAreasEnrichment::class, 'admin_areas-enrichable');
    }
}
