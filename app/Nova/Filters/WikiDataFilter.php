<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class WikiDataFilter extends BooleanFilter
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
        if ($value['has_wikidata']) {
            return $query->whereRaw("jsonb_exists(cast(tags as jsonb), 'wikidata')");
        }

        if ($value['no_wikidata']) {
            return $query->whereRaw("NOT jsonb_exists(cast(tags as jsonb), 'wikidata')");
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
            'Has Wikidata' => 'has_wikidata',
            'No Wikidata' => 'no_wikidata',
        ];
    }
}
