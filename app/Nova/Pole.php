<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\DateTime;
use Rpj\Daterangepicker\DateHelper;
use Illuminate\Support\Facades\Date;
use App\Nova\Filters\PolesElevationFilter;
use Illuminate\Support\Facades\DB;
use Outl1ne\NovaTooltipField\Tooltip;
use Rpj\Daterangepicker\Daterangepicker;
use Laravel\Nova\Http\Requests\NovaRequest;

class Pole extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Pole>
     */
    public static $model = \App\Models\Pole::class;

    public static function newModel()
    {
        $model = parent::newModel();
        $model->setKeyName('osm_id');

        return $model;
    }

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'osm_id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'osm_id', 'name', 'ref', 'destination'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('OSM ID', 'osm_id')->sortable()->displayUsing(
                function ($value) {
                    switch ($this->osm_type) {
                        case 'N':
                            return "<a style='color:green;' href='https://www.openstreetmap.org/node/$value' target='_blank'>$value</a>";
                        case 'W':
                            return "<a style='color:green;' href='https://www.openstreetmap.org/way/$value' target='_blank'>$value</a>";
                        case 'R':
                            return "<a style='color:green;' href='https://www.openstreetmap.org/relation/$value' target='_blank'>$value</a>";
                    }
                }
            )->asHtml(),
            Text::make('OSM Type', 'osm_type')->displayUsing(
                function ($value) {
                    return "<div style='font-size: 1.2em; border: 1px solid black; font-weight: bold; text-align:center;'>$value</div>";
                }
            )->asHtml()
                ->sortable()
                ->onlyOnIndex(),
            Text::make('OSM Type')
                ->onlyOnDetail(),
            DateTime::make('Updated_at')
                ->displayUsing(
                    function ($value) {
                        return Carbon::parse($value)->toIso8601String();
                    }
                )->sortable(),
            Tooltip::make('Tags', 'tags')
                ->iconFromPath(public_path('images/eye-svgrepo-com.svg'))
                ->content($this->tags)
                ->onlyOnIndex(),
            Code::make('Tags')->json()->hideFromIndex(),
            Text::make('Wiki', function () {
                return $this->getWikiLinks();
            })->asHtml()->hideWhenCreating()->hideWhenUpdating(),
            Text::make('Name'),
            Text::make('Ref'),
            Text::make('Destination', function () {
                return wordwrap($this->destination, 50, '<br>', true);
            })->asHtml(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new Filters\WikiDataFilter(),
            new Filters\WikiMediaFilter(),
            new Filters\WikiPediaFilter(),
            new Filters\OsmTypeFilter(),
            PolesElevationFilter::make()
                ->dividerLabel('<>')
                ->inputType('number')
                ->placeholder('From', 'To')
                ->fromAttributes(['min' => DB::table('poles')->min('ele')])
                ->toAttributes(['max' => DB::table('poles')->max('ele')]),
            new Daterangepicker('updated_at', DateHelper::ALL, 'poles.name', 'desc')

        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
