<?php

namespace App\Nova\Cards;

use Abordage\HtmlCard\HtmlCard;
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
      //get the record from osm2pgsql_crontab_updates table with the latest imported_at date
      $lastLuaUpdate = \App\Models\Osm2pgsqlCrontabUpdate::where('success', true)->orderBy('imported_at', 'desc')->first();

      if ($lastLuaUpdate) {
         return '<h1 class="text-4xl">Last Lua Update</h1><p class="text-lg text-gray-400 text-center">Imported at: ' . $lastLuaUpdate->imported_at . '</p>';
      } else {
         //last lua update should be the today date at 00:00:00
         return '<h1 class="text-4xl text-center shadow">Last Lua Update</h1><p class="text-sm text-gray-400 text-center pt-3">Imported at: ' . Carbon::parse(date('Y-m-d'))->toIso8601String() . '</p>';
      }
   }
}
