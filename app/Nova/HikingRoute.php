<?php

namespace App\Nova;

use App\Nova\Actions\DemEnrichmentAction;
use Laravel\Nova\Panel;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Rpj\Daterangepicker\DateHelper;
use Outl1ne\NovaTooltipField\Tooltip;
use Rpj\Daterangepicker\Daterangepicker;
use Laravel\Nova\Http\Requests\NovaRequest;

class HikingRoute extends OsmFeaturesResource
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
        $osmfeaturesFields = parent::fields($request);
        unset($osmfeaturesFields[1]); //remove datetime
        //get dem enrichment associated
        $demEnrichment = $this->demEnrichment ? json_decode($this->demEnrichment->data, true)['properties'] ?? null : null;

        $specificFields = [
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
            Boolean::make('Has Invalid Geometry', 'has_invalid_geometry')->sortable()
                ->onlyOnDetail()
        ];

        if ($demEnrichment) {
            $specificFields = array_merge($specificFields, [
                Tooltip::make('DEM Data')
                    ->iconFromPath(public_path('images/pricetags-outline.svg'))
                    ->content(
                        collect($demEnrichment)->map(function ($value, $key) {
                            //check if $value is a boolean and translate it to a string
                            if (is_bool($value)) {
                                $value =  $value ? 'True' : 'False';
                            }
                            return "<span style='font-weight: bold; color: #7b4896';>{$key}:</span> : {$value}";
                        })->implode('<br>')
                    )
                    ->hideWhenCreating()
                    ->hideWhenUpdating()
                    ->allowTooltipHTML()
                    ->onlyOnDetail(),
            ]);
        }

        $finalFields = array_merge($osmfeaturesFields, $specificFields);

        return $finalFields;
    }

    public function actions(NovaRequest $request)
    {
        //remove parent actions
        $defaultActions = parent::actions($request);
        $specificActions = [
            (new DemEnrichmentAction())->canRun(function () {
                return true;
            })
        ];
        $actions = array_merge($defaultActions, $specificActions);
        unset($actions[0]);
        return $actions;
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
        $specifiFilters = [
            new Filters\DemEnrichmentFilter(),
            new Filters\CaiScaleFilter(),
            new Filters\Osm2caiStatusFilter(),
            new Filters\HasInvalidGeometryFilter(),
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
