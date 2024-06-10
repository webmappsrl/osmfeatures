<?php

namespace App\Nova;

use App\Nova\Filters\ElevationFilter;
use App\Nova\OsmFeaturesResource;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Place extends OsmFeaturesResource
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
        $model->setKeyName('id');

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
        $osmfeaturesFields = parent::fields($request);

        $specificFields = [
            Text::make('Class')
                ->sortable(),
            Text::make('Subclass')
                ->sortable(),
            Text::make('Elevation')->sortable()->displayUsing(
                function ($value) {
                    if ($value) {
                        return $value.' m';
                    } else {
                        return ' ';
                    }
                }
            ),
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
            new Filters\ClassFilter(),
            new Filters\SubclassFilter(),
            ElevationFilter::make()
                ->dividerLabel('<>')
                ->inputType('number')
                ->placeholder('From', 'To')
                ->fromAttributes(['min' => 0])
                ->toAttributes(['max' => 10000]),
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
