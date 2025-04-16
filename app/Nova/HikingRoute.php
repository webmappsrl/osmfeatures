<?php

namespace App\Nova;

use App\Nova\Actions\calculateAdminAreasIntersecting;
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
use Wm\MapMultiLinestring\MapMultiLinestring;

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
        'osm_id',
        'name',
        'ref',
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
        //get admin areas intersecting
        $adminAreasIntersecting = $this->adminAreasEnrichment ? json_decode($this->adminAreasEnrichment->data, true) ?? null : null;

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
            MapMultiLinestring::make('geom')->withMeta([
                'center' => [42, 10],
                'attribution' => '<a href="https://webmapp.it/">Webmapp</a> contributors',
                'tiles' => 'https://api.webmapp.it/tiles/{z}/{x}/{y}.png',
                'minZoom' => 5,
                'maxZoom' => 17,
                'defaultZoom' => 10,
            ])->onlyOnDetail(),
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
            Tooltip::make('Admin Areas', 'admin_areas')
                ->iconFromPath(public_path('images/admin_areas.svg'))
                ->content(
                    collect($adminAreasIntersecting)->map(function ($levels) {
                        if (is_array($levels)) {
                            $htmlString = '';

                            // Itera attraverso ogni array di aree per il livello corrente
                            foreach ($levels as $level => $areas) {
                                $htmlString .= "<div style='margin-bottom: 10px;'><strong style='color:#005f73; font-size: 1.05rem; font-weight: bold;'>Admin Level {$level}</strong>:<br>";
                                if (is_array($areas)) {
                                    foreach ($areas as $area) {
                                        // Controlla che ogni elemento sia un array con le chiavi necessarie
                                        if (isset($area['name']) && isset($area['osmfeatures_id'])) {
                                            $htmlString .= "<div style='margin-bottom: 5px; padding: 5px;'><strong style='color:#047bff;'>Name:</strong> <span style='color:#333;'>{$area['name']}</span><br><strong style='color:#007bff;'>OSM ID:</strong> <span style='color:#333;'>{$area['osmfeatures_id']}</span></div>";
                                        }
                                    }
                                }
                                $htmlString .= "</div>";
                            }
                            return $htmlString;
                        }
                        return '';
                    })->implode('<br>')
                )
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->allowTooltipHTML()
                ->onlyOnDetail(),

        ];

        if ($demEnrichment) {
            $content = collect($demEnrichment)->map(function ($value, $key) {
                //check if $value is a boolean and translate it to a string
                if (is_bool($value)) {
                    $value =  $value ? 'True' : 'False';
                }
                return "<span style='font-weight: bold; color: #7b4896';>{$key}:</span> : {$value}";
            })->implode('<br>');
        } else {
            $content = 'DEM data not available';
        } {
            $specificFields = array_merge($specificFields, [
                Tooltip::make('DEM Data')
                    ->iconFromPath(public_path('images/dem.svg'))
                    ->content($content)
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
            }),
            (new calculateAdminAreasIntersecting())->canRun(function () {
                return true;
            }),
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
            new Filters\AdminAreasEnrichmentFilter(),
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
