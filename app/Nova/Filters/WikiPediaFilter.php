<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class WikiPediaFilter extends BooleanFilter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        if ($value['has_wikipedia']) {
            return $query->whereRaw("jsonb_exists(cast(tags as jsonb), 'wikipedia')");
        }

        if ($value['no_wikipedia']) {
            return $query->whereRaw("NOT jsonb_exists(cast(tags as jsonb), 'wikipedia')");
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'Has wikipedia' => 'has_wikipedia',
            'No wikipedia' => 'no_wikipedia',
        ];
    }
}
