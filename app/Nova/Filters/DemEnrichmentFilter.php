<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use App\Nova\Filters\EnrichmentFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class DemEnrichmentFilter extends EnrichmentFilter
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
        if ($value['Enriched']) {
            return $query->whereHas('demEnrichment');
        } elseif ($value['Not enriched']) {
            return $query->whereDoesntHave('demEnrichment');
        } else {
            return $query;
        }
    }
}
