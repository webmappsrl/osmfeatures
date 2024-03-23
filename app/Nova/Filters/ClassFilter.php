<?php

namespace App\Nova\Filters;

use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class ClassFilter extends Filter
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
     * @param  NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('class', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        //query the database in the places table to get all the classes value not repeating it and return it as an array
        $query = 'SELECT DISTINCT class FROM places WHERE class IS NOT NULL';
        $results = DB::select($query);

        return collect($results)->mapWithKeys(function ($item) {
            return [$item->class => $item->class];
        })->toArray();
    }
}
