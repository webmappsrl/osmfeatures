<?php

namespace App\Jobs;

use App\Models\DemEnrichment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DemEnrichmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;


    /**
     * Create a new job instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = Log::channel('dem-enrichment');
        $demApi = 'https://dem.maphub.it/api/v1/track';
        $logger->debug('DemEnrichmentJob started');

        //demApi expects a geojson as body to be sent in the request. we will create the geojson based on the feature id retriving data from the database

        $geojson = $this->getGeojsonForDem($this->model, $logger);
        $logger->debug('DemEnrichmentJob geojson created');

        //make the request to the dem api with the geojson as the body of the request
        $response = Http::timeout(60)->post($demApi, $geojson);
        $logger->debug('DemEnrichmentJob request sent.');

        if (!$response->successful()) {
            //get the error code from the response
            $logger->error('DemEnrichmentJob request failed for model ' . get_class($this->model) . ' with id ' . $this->model->id, ['status' => $response->status(), 'reason' => $response->reason()]);
            return;
        }

        //get the json from the response
        $data = $response->json();
        $logger->debug('DemEnrichmentJob response received');

        //create a new demEnrichment Record in the database using the data from the response
        DemEnrichment::updateOrCreate([
            'enrichable_osmfeatures_id' => $this->model->getOsmfeaturesId(),
        ], [
            'dem-enrichable_id' => $this->model->id,
            'dem-enrichable_type' => get_class($this->model),
            'data' => json_encode($data),
        ]);
        $logger->debug('DemEnrichmentJob record created');
    }

    private function getGeojsonForDem($model, $logger)
    {

        //get the model table
        $modelTable = $model->getTable();
        $geojson = [];
        $geojson['type'] = 'Feature';

        $properties = [];
        $properties['id'] = $model->id;

        $geometry = DB::select("SELECT ST_AsGeoJSON(geom) as geom FROM $modelTable WHERE id = $model->id");

        $geojson['properties'] = $properties;
        $geojson['geometry'] = json_decode($geometry[0]->geom);

        $logger->debug('DemEnrichmentJob geojson created');

        return $geojson;
    }
}