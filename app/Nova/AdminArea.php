<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\NovaTooltipField\Tooltip;
use Rpj\Daterangepicker\DateHelper;
use Rpj\Daterangepicker\Daterangepicker;

class AdminArea extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\AdminArea>
     */
    public static $model = \App\Models\AdminArea::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'osm_id', 'name',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        \Log::info($query->toSql());

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
        return [
            Text::make('Details')->displayUsing(function () {
                $name = wordwrap($this->name, 50, '<br>', true);

                $osmIdLink = <<<HTML
                <a style='color:green;' href='https://www.openstreetmap.org/relation/{$this->osm_id}' target='_blank'>
                    <span style='font-weight: bold;'>OSM ID:</span> {$this->osm_id}
                </a>
                HTML;

                $osmType = <<<HTML
                <span>
                    <span style='font-weight: bold;'>OSM Type:</span> {$this->osm_type}
                </span>
                HTML;

                return <<<HTML
                $osmIdLink<br>
                $osmType<br>
                <span style='font-weight: bold;'>Name:</span> $name
                HTML;
            })->asHtml()->onlyOnIndex(),

            Text::make('OSM Type')
                ->onlyOnDetail(),
            DateTime::make('Updated_at')
                ->displayUsing(
                    function ($value) {
                        return Carbon::parse($value)->toIso8601String();
                    }
                )->sortable(),
            Tooltip::make('Tags', 'tags')
                ->iconFromPath(public_path('images/pricetags-outline.svg'))
                ->content(
                    collect(json_decode($this->tags, true))->map(function ($value, $key) {
                        return "{$key}: {$value}";
                    })->implode('<br>')
                )
                ->onlyOnIndex()
                ->allowTooltipHTML(),
            Code::make('Tags')->json()->hideFromIndex(),
            Text::make('Wiki', function () {
                return $this->getWikiLinksAsHtml();
            })->asHtml()->hideWhenCreating()->hideWhenUpdating()->textAlign('center'),
            Text::make('Name')->displayUsing(
                function ($value) {
                    //max length should be 50 characters then break the line
                    return wordwrap($value, 50, '<br>', true);
                }
            )->asHtml(),
            Text::make('Level', 'admin_level')
                ->sortable(),
            Number::make('Score', 'score')
                ->displayUsing(function ($value) {
                    //return a star rating
                    $stars = '';

                    if ($value == 0 || $value == null) {
                        return 'No rating';
                    }
                    for ($i = 0; $i < $value; $i++) {
                        $stars .= '⭐';
                    }

                    return $stars;
                })->sortable()->filterable(),
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
            new Daterangepicker('updated_at', DateHelper::ALL),
            new Filters\AdminLevelFilter(),
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
