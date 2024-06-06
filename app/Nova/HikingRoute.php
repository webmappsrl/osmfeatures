<?php

namespace App\Nova;

use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\NovaTooltipField\Tooltip;
use Rpj\Daterangepicker\DateHelper;
use Rpj\Daterangepicker\Daterangepicker;

class HikingRoute extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\HikingRoute>
     */
    public static $model = \App\Models\HikingRoute::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'osm_id', 'name', 'ref',
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
            Text::make('OSM ID', 'osm_id')->sortable()->displayUsing(
                function ($value) {
                    $link = $this->getOsmUrl();
                    return "<a style='color:green;' href='$link' target='_blank'>$value</a>";
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
            DateTime::make('Updated_at_osm')
                ->sortable()
                ->displayUsing(
                    function ($value) {
                        return Carbon::parse($value)->toIso8601String();
                    }
                ),
            Tooltip::make('Tags', 'tags')
                ->iconFromPath(public_path('images/pricetags-outline.svg'))
                ->content(
                    collect(json_decode($this->tags, true))->map(function ($value, $key) {
                        return "{$key}: {$value}";
                    })->implode('<br>')
                )
                ->allowTooltipHTML()
                ->onlyOnIndex(),
            Code::make('Tags')->json()->hideFromIndex(),
            Text::make('Wiki', function () {
                return $this->getWikiLinksAsHtml();
            })->asHtml()->hideWhenCreating()->hideWhenUpdating()->textAlign('center')->fullWidth(),
            Text::make('Specs', function () {
                $tags = json_decode($this->tags, true);
                $ref = $tags['ref'] ?? 'N/A';
                $source = $tags['source'] ?? 'N/A';
                $cai_scale = $tags['cai_scale'] ?? 'N/A';
                $name = $this->name ?? 'N/A';

                $name = strlen($name) > 30 ? substr($name, 0, 30) . '<br>' . substr($name, 30) : $name;

                $html = '<div>';
                $html .= "<p><strong>ref:</strong> {$ref}</p>";
                $html .= "<p><strong>source:</strong> {$source}</p>";
                $html .= "<p><strong>cai_scale:</strong> {$cai_scale}</p>";
                $html .= '<p><strong>name:</strong> ' . $name . '</p>';
                $html .= '</div>';

                return $html;
            })
                ->asHtml(),
            Text::make('Osm2cai Status')
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
            new Filters\CaiScaleFilter(),
            new Filters\Osm2caiStatusFilter(),

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
