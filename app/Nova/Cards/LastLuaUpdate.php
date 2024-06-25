<?php

namespace App\Nova\Cards;

use Abordage\HtmlCard\HtmlCard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LastLuaUpdate extends HtmlCard
{
    /**
     * The width of the card (1/2, 1/3, 1/4 or full).
     */
    public $width = '1/3';

    /**
     * The height strategy of the card (fixed or dynamic).
     */
    public $height = 'fixed';

    /**
     * Align content to the center of the card.
     */
    public bool $center = true;

    /**
     * Html content
     */
    public function content(): string
    {
        // Get the record from planet_osm_replication_status table with the latest importdate
        $lastLuaUpdateRecord = DB::select("SELECT MAX(importdate) AS importdate FROM planet_osm_replication_status")[0];

        if ($lastLuaUpdateRecord && $lastLuaUpdateRecord->importdate) {
            $lastLuaUpdate = Carbon::parse($lastLuaUpdateRecord->importdate);

            return '<h1 class="text-4xl">Last Lua Update</h1><p class="text-lg text-gray-400 text-center">' . $lastLuaUpdate->toDateTimeString() . '</p>';
        } else {
            return '<h1 class="text-4xl">Last Lua Update</h1><p class="text-lg text-gray-400 text-center">No data found</p>';
        }
    }
}
