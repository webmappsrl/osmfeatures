<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class AdminArea extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\AdminArea>
     */
    public static $model = \App\Models\AdminArea::class;

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
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'osm_id', 'name'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('OSM ID', 'osm_id')->sortable()->displayUsing(
                function ($value) {
                    return "<a style='color:green;' href='https://www.openstreetmap.org/relation/$value' target='_blank'>$value</a>";
                }
            )->asHtml(),
            Text::make('Name'),
            Text::make('OSM Type', 'osm_type'),
            Text::make('Admin Level', 'admin_level'),
            Text::make('Tags')->displayUsing(
                function ($value) {
                    $json = json_decode($value, true);
                    $json = preg_replace(
                        '/(".*?"):(.*?)(,|$)/',
                        '<span style="color:darkgreen;">$1</span>: $2$3<br>',
                        wordwrap(json_encode($json), 200, '<br>', true)
                    );

                    return $json;
                }
            )->asHtml(),

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
