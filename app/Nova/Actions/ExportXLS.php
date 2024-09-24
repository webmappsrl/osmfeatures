<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Carbon;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Http\Requests\NovaRequest;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportXLS extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Export XLS';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $modelsArray = $models->map(function ($model) {
            return [
                'nova_id' => $model->id,
                'osmfeatures_id' => $model->getOsmfeaturesId(),
                'type' => strtolower(class_basename($model)),
                'osm_id' => $model->osm_id,
                'osm_url' => $model->getOsmUrl(),
            ];
        })->toArray();

        $fileName = $fields->file_name;

        // Redirect to the export route
        return Action::redirect(route('export.excel', [
            'models' => json_encode($modelsArray),
            'file_name' => $fileName,
            'type' => strtolower(class_basename($models->first())),
        ]));
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('File Name', 'file_name')
                ->default(function ($request) {
                    $resource = class_basename($request->resource);
                    $timestamp = Carbon::now()->format('Y_m_d_H_i');
                    return "osmfeatures_export_{$resource}_{$timestamp}.xls";
                })
                ->rules('required', 'string', 'max:255'),
        ];
    }
}
