<?php

namespace App\Nova\Filters;

use DigitalCreative\RangeInputFilter\RangeInputFilter;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PolesElevationFilter extends RangeInputFilter
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
        $from = data_get($value, 'from');
        $to = data_get($value, 'to');

        return $query->where('ele', '>=', $from)
            ->where('ele', '<=', $to);
    }

    /**
     * Get the filter's available options.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [];
    }
}
