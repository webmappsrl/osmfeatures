<?php

namespace App\Nova\Cards;

use Abordage\HtmlCard\HtmlCard;

class PolesCount extends HtmlCard
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
      $polesCount = \App\Models\Pole::count();
      return '<h1 class="text-4xl shadow">Poles</h1><p class="text-lg pt-3 text-center">' . $polesCount . '</p>';
   }
}
