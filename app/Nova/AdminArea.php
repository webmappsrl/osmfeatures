<?php

namespace App\Nova;

use Laravel\Nova\Fields\Text;
use App\Nova\OsmFeaturesResource;
use Laravel\Nova\Http\Requests\NovaRequest;

class AdminArea extends OsmFeaturesResource
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
            Text::make('Level', 'admin_level')
                ->sortable(),
        ];

        return array_merge($osmfeaturesFields, $specificFields);
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

        $specificFilters = [
            new Filters\AdminLevelFilter(),

        ];

        return array_merge($osmfeaturesFilters, $specificFilters);
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
