<?php

namespace App\Nova\Filters;

use Laravel\Nova\Http\Requests\NovaRequest;
use DigitalCreative\RangeInputFilter\RangeInputFilter;

class ElevationFilter extends RangeInputFilter
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
        $from = data_get($value, 'from');
        $to = data_get($value, 'to');

        return $query->whereRaw("CAST(tags->>'ele' AS NUMERIC) >= ?", [$from])
            ->whereRaw("CAST(tags->>'ele' AS NUMERIC) <= ?", [$to]);
    }


    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [];
    }
}