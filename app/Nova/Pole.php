<?php

namespace App\Nova;

use Wm\MapPoint\MapPoint;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Facades\DB;
use App\Nova\Filters\PolesElevationFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Filters\PolesProposedFilter;
use Illuminate\Support\Facades\Log;

class Pole extends OsmFeaturesResource
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
        $model->setKeyName('id');

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
        'osm_id',
        'name',
        'ref',
        'destination',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        Log::info($query->toSql());

        return $query;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $osmfeaturesFields = parent::fields($request);

        $specificFields = [

            Text::make('Name'),
            Text::make('Ref'),
            Text::make('Destination', function () {
                return wordwrap($this->destination, 50, '<br>', true);
            })->asHtml(),
            MapPoint::make('geom')->withMeta([
                'center' => [42, 10],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'minZoom' => 8,
                'maxZoom' => 17,
                'defaultZoom' => 14,
            ])->onlyOnDetail(),
        ];

        $finalFields = array_merge($osmfeaturesFields, $specificFields);

        return $finalFields;
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
        $osmfeaturesFilters = parent::filters($request);
        $specifiFilters =  [
            new PolesProposedFilter(),
            PolesElevationFilter::make()
                ->dividerLabel('<>')
                ->inputType('number')
                ->placeholder('From', 'To')
                ->fromAttributes(['min' => DB::table('poles')->min('ele')])
                ->toAttributes(['max' => DB::table('poles')->max('ele')]),
        ];

        return array_merge($osmfeaturesFilters, $specifiFilters);
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
}
