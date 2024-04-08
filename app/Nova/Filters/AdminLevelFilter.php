<?php

namespace App\Nova\Filters;

use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class AdminLevelFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    public $name = 'Admin Level';

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
        return $query->where('admin_level', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        $admin_levels = DB::select('SELECT DISTINCT admin_level FROM admin_areas ORDER BY admin_level ASC');
        $options = [];
        foreach ($admin_levels as $admin_level) {
            $options[$admin_level->admin_level] = $admin_level->admin_level;
        }
        ksort($options);

        return $options;
    }
}
