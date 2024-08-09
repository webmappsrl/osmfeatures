<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAreasEnrichment extends Model
{
    use HasFactory;

    public function adminAreasEnrichable()
    {
        return $this->morphTo('admin_areas-enrichable', 'admin_areas-enrichable_type', 'admin_areas-enrichable_id');
    }
}
