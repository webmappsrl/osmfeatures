<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Textarea;
use Rpj\Daterangepicker\DateHelper;
use App\Nova\Filters\ElevationFilter;
use Outl1ne\NovaTooltipField\Tooltip;
use Rpj\Daterangepicker\Daterangepicker;
use Laravel\Nova\Http\Requests\NovaRequest;

class Place extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Place>
     */
    public static $model = \App\Models\Place::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'osm_id';

    public static function newModel()
    {
        $model = parent::newModel();
        $model->setKeyName('osm_id');

        return $model;
    }

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name', 'class', 'subclass', 'osm_id',
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
            Text::make('Name')->displayUsing(
                function ($value) {
                    //max length should be 50 characters then break the line
                    return wordwrap($value, 50, '<br>', true);
                }
            )->asHtml(),
            Text::make('Class')
                ->sortable(),
            Text::make('Subclass')
                ->sortable(),
            Text::make('Elevation', 'tags')->sortable()->displayUsing(
                function ($value) {
                    $ele = json_decode($value, true)['ele'] ?? null;
                    $ele = $ele ? $ele . ' m' : null;

                    return $ele;
                }
            ),
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
            new Filters\ClassFilter(),
            new Filters\SubclassFilter(),
            ElevationFilter::make()
                ->dividerLabel('<>')
                ->inputType('number')
                ->placeholder('From', 'To')
                ->fromAttributes(['min' => 0])
                ->toAttributes(['max' => 10000]),
            new Filters\OsmTypeFilter(),
            new Daterangepicker('updated_at', DateHelper::ALL, 'places.name', 'desc')

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
