<?php

namespace App\Nova\Cards;

use Abordage\HtmlCard\HtmlCard;

class AdminAreasCount extends HtmlCard
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
      $adminAreasCount = \App\Models\AdminArea::count();
      return '<h1 class="text-4xl shadow ">Admin Areas</h1><p class="text-2xl text-center pt-3 ">' . $adminAreasCount . '</p>';
   }
}
