<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Outl1ne\NovaTooltipField\Tooltip;

class Poi extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Poi>
     */
    public static $model = \App\Models\Poi::class;

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
                ->sortable(),
            Text::make('Updated At', function () {
                return Carbon::parse($this->updated_at)->toIso8601String();
            }),
            Text::make('Name'),
            Text::make('Class'),
            Text::make('Subclass'),
            // Text::make('Tags')->displayUsing(
            //     function ($value) {
            //         $json = json_decode($value, true);
            //         //wordwrap the json to make it more readable and add a color to the keys
            //         $json = preg_replace(
            //             '/(".*?"):(.*?)(,|$)/',
            //             '<span style="color:darkgreen;">$1</span>: $2$3<br>',
            //             wordwrap(json_encode($json), 75, '<br>', true)
            //         );

            //         return $json;
            //     }
            // )->asHtml(),
            // Text::make('Tags', function () {
            //     return '<a style="color:blue;" href="' . route('tags-details', ['resource' => 'Poi', 'resourceId' => $this->osm_id]) . '" target="_blank">Tags</a>';
            // })->asHtml(),
            Tooltip::make('Tags', 'tags')
                ->iconFromPath(public_path('images/eye-svgrepo-com.svg'))
                ->content($this->tags),
            Text::make('WikiData', function () {
                return '<a style="color:blue;" href="https://www.wikidata.org/wiki/' . $this->getWikidata() . '" target="_blank">' . $this->getWikidata() . '</a>';
            })->hideWhenCreating()
                ->hideWhenUpdating()
                ->asHtml(),
            Text::make('WikiMedia', function () {
                return '<a style="color:blue;" href="https://commons.wikimedia.org/wiki/' . $this->getWikimediaCommons() . '" target="_blank">' . $this->getWikimediaCommons() . '</a>';
            })->hideWhenCreating()
                ->hideWhenUpdating()
                ->asHtml(),
            Text::make('WikiPedia', function () {
                return '<a style="color:blue;" href="https://en.wikipedia.org/wiki/' . $this->getWikipedia() . '" target="_blank">' . $this->getWikipedia() . '</a>';
            })->hideWhenCreating()
                ->hideWhenUpdating()
                ->asHtml(),
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
