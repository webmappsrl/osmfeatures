<?php

namespace App\Nova\Dashboards;

use App\Nova\Cards\HrCount;
use Laravel\Nova\Dashboard;
use App\Nova\Cards\PlacesCount;
use App\Nova\Cards\AdminAreasCount;
use App\Nova\Cards\LastLuaUpdate;
use App\Nova\Cards\PolesCount;

class Features extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            (new HrCount()),
            (new AdminAreasCount()),
            (new PlacesCount()),
            (new PolesCount()),
            (new LastLuaUpdate()),
        ];
    }

    /**
     * Get the URI key for the dashboard.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'features';
    }
}
