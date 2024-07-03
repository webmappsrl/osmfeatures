<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\DateTime;
use Outl1ne\NovaTooltipField\Tooltip;
use Laravel\Nova\Http\Requests\NovaRequest;

class Enrichment extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Enrichment>
     */
    public static $model = \App\Models\Enrichment::class;

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
        'id',
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
            ID::make()->sortable(),
            MorphTo::make('Enrichable')->filterable(),
            DateTime::make('Created at'),
            DateTime::make('Updated at'),
            Text::make('Enrichable Osmfeatures ID', 'enrichable_osmfeatures_id'),
            Tooltip::make('Data', 'data')
                ->iconFromPath(public_path('images/pricetags-outline.svg'))
                ->content(
                    collect(json_decode($this->data, true))->map(function ($value, $key) {
                        // Check if $value is an array
                        if (is_array($value)) {
                            // If $value is an array, format it properly
                            $subContent = collect($value)->map(function ($subValue, $subKey) {
                                return "<span style='font-weight: bold; color: #3b82f6';>{$subKey}:</span> {$subValue}";
                            })->implode('<br>');
                            return "<span style='font-weight: bold; color: #3b82f6';>{$key}:</span><br>{$subContent}";
                        }
                        // If $value is not an array, handle it as a string
                        return "<span style='font-weight: bold; color: #3b82f6';>{$key}:</span> {$value}";
                    })->implode('<br>')
                )
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->allowTooltipHTML(),

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
