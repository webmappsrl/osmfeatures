<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class AdminAreasEnrichmentFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value == 1) {
            return $query->whereHas('adminAreasEnrichment', function ($q) {
                $q->where('admin_areas-enrichable_type', 'App\\Models\\HikingRoute');
            });
        } elseif ($value == 0) {
            return $query->whereDoesntHave('adminAreasEnrichment', function ($q) {
                $q->where('admin_areas-enrichable_type', 'App\\Models\\HikingRoute');
            });
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'With Enrichments' => 1,
            'Without Enrichments' => 0,
        ];
    }
}
