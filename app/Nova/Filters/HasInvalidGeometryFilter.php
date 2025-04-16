<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class HasInvalidGeometryFilter extends BooleanFilter
{
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
        switch (true) {
            case $value['Valid']:
                return $query->whereHas('demEnrichment', function ($q) {
                    $q->where('enrichable_has_invalid_geometry', false);
                });
                break;
            case $value['Invalid']:
                return $query->whereHas('demEnrichment', function ($q) {
                    $q->where('enrichable_has_invalid_geometry', true);
                });
                break;
            default:
                return $query;
        }
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return ['Valid' => 'Valid', 'Invalid' => 'Invalid'];
    }
}
