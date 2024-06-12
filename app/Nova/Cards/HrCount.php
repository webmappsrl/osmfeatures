<?php

namespace App\Nova\Cards;

use Abordage\HtmlCard\HtmlCard;

class HrCount extends HtmlCard
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
        $hrCount = \App\Models\HikingRoute::count();

        return '<h1 class="text-4xl shadow ">Hiking Routes</h1> <p class="text-2xl text-center pt-3 ">' . $hrCount . '</p>';
    }
}
