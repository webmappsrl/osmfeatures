<?php

namespace App\Nova\Cards;

use Abordage\HtmlCard\HtmlCard;
use App\Models\Osm2pgsqlCrontabUpdate;
use Carbon\Carbon;

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
        $lastSuccess = Osm2pgsqlCrontabUpdate::query()
            ->where('success', true)
            ->latest('imported_at')
            ->first();

        $title = '<h1 class="text-4xl">Last DB Import</h1>';

        if (! $lastSuccess) {
            return $title.'<p class="text-lg text-gray-400 text-center">No data found</p>';
        }

        $lastSuccessTime = Carbon::parse($lastSuccess->imported_at);
        $output = $title
            .'<p class="text-lg text-gray-400 text-center">'
            .$lastSuccessTime->toDateTimeString()
            .'</p>';

        $failedAfter = Osm2pgsqlCrontabUpdate::query()
            ->where('success', false)
            ->where('imported_at', '>', $lastSuccess->imported_at)
            ->exists();

        if ($failedAfter) {
            $output .= '<p class="text-sm text-red-500 text-center mt-2">'
                ."⚠️ Import falliti dopo l'ultimo successo"
                .'</p>';
        }

        return $output;
    }
}
