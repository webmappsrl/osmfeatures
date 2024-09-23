<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Http\Requests\NovaRequest;

class GeojsonDownload extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Download GeoJSON';
    protected $propertyKeys;

    public function __construct(array $propertyKeys = [])
    {
        $this->propertyKeys = $propertyKeys;
    }


    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $features = [];
        $model = $models->first();
        //get the name for the model class
        $modelClass = get_class($model);
        $modelClass = explode('\\', $modelClass);
        $modelClass = array_pop($modelClass);
        $fileName = strtolower($modelClass);

        foreach ($models as $model) {
            $feature = $model->getGeojsonFeature();
            if ($feature) {
                $properties = $feature['properties'];

                //filter the properties
                if (!empty($this->propertyKeys))
                    $properties = array_intersect_key($properties, array_flip($this->propertyKeys));

                $feature['properties'] = $properties;
                $features[] = $feature;
            }
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        Storage::disk('public')->put($fileName, json_encode($geojson));

        return Action::download(Storage::url($fileName), '' . $fileName . 's_' . Carbon::now()->toDateString() . '.geojson');
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
