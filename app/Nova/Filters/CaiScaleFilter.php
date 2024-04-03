<?php

namespace App\Nova\Filters;

use Illuminate\Support\Facades\DB;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Psy\Command\WhereamiCommand;

class CaiScaleFilter extends Filter
{

    public $name = 'CAI Scale';
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
        return $query->where('cai_scale', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        //get all the cai_scale distinct values from the hiking_Routes table
        $cai_scales = DB::select('SELECT DISTINCT cai_scale FROM hiking_routes ORDER BY cai_scale ASC');
        $options = [];
        foreach ($cai_scales as $cai_scale) {
            $options[$cai_scale->cai_scale] = $cai_scale->cai_scale;
        }
        ksort($options);
        //remove the null value
        unset($options['']);
        return $options;
    }
}
