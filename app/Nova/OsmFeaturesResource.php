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

class OsmFeaturesResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static $model = \App\Models\AdminArea::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     */
    public static $title;

    /**
     * The columns that should be searched.
     */
    public static $search;

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
                if (!$this->name) {
                    $name = '-';
                } else {
                    $name = wordwrap($this->name, 50, '<br>', true);
                }

                $link = $this->getOsmUrl();

                $osmIdLink =
                    <<<HTML
                        <a style='color:green;' href='{$link}' target='_blank'>
                            <span style='font-weight: bold;'>OSM ID:</span> {$this->osm_id}
                        </a>
                        HTML;

                $osmType =
                    <<<HTML
                            <span>
                                <span style='font-weight: bold;'>OSM Type:</span> {$this->osm_type}
                            </span>
                        HTML;

                return <<<HTML
                    $osmIdLink<br>
                    $osmType<br>
                    <span style='font-weight: bold;'>Name:</span> $name
                    HTML;
            })->asHtml()->hideWhenCreating()->hideWhenUpdating(),

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
