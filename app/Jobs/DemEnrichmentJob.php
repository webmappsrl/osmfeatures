<?php

namespace App\Jobs;

use Exception;
use App\Models\DemEnrichment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Psr\Log\LoggerInterface;

class DemEnrichmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The model to be enriched with DEM data.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * The DEM API endpoint.
     *
     * @var string
     */
    private const DEM_API_ENDPOINT = 'https://dem.maphub.it/api/v1/track';

    /**
     * The HTTP request timeout in seconds.
     *
     * @var int
     */
    private const REQUEST_TIMEOUT = 120;

    /**
     * Create a new job instance.
     *
     * @param Model $model The model to be enriched
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $logger = $this->getLogger();
        $logger->debug('DemEnrichmentJob started');

        try {
            $geojson = $this->prepareGeojsonData($logger);
            $response = $this->sendDemApiRequest($geojson, $logger);
            $this->processSuccessfulResponse($response, $logger);
        } catch (Exception $e) {
            $logger->error('DemEnrichmentJob failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the logger instance.
     *
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return Log::channel('dem-enrichment');
    }

    /**
     * Prepare the GeoJSON data for the DEM API.
     *
     * @param LoggerInterface $logger
     * @return array
     */
    private function prepareGeojsonData(LoggerInterface $logger): array
    {
        $geojson = $this->createGeojsonFromModel($this->model, $logger);
        if ($geojson['geometry'] === null) {
            throw new Exception('GeoJSON geometry is null');
        }
        $logger->debug('DemEnrichmentJob geojson created');

        return $geojson;
    }

    /**
     * Send request to the DEM API.
     *
     * @param array $geojson
     * @param LoggerInterface $logger
     * @return \Illuminate\Http\Client\Response
     * @throws Exception
     */
    private function sendDemApiRequest(array $geojson, LoggerInterface $logger)
    {
        $response = Http::timeout(self::REQUEST_TIMEOUT)->post(self::DEM_API_ENDPOINT, $geojson);
        $logger->debug('DemEnrichmentJob request sent');

        if (!$response->successful()) {
            $this->handleFailedResponse($response, $logger);
        }

        return $response;
    }

    /**
     * Handle a failed API response.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param LoggerInterface $logger
     * @throws Exception
     */
    private function handleFailedResponse($response, LoggerInterface $logger): void
    {
        $modelClass = get_class($this->model);
        $modelId = $this->model->id;

        if (isset($response->json()['error'])) {
            $errorMessage = $response->json()['error'];
            $logger->error("DemEnrichmentJob request failed for model {$modelClass} with id {$modelId}", [
                'reason' => $errorMessage,
            ]);

            $this->markModelAsInvalidGeometry();
            throw new Exception($errorMessage);
        }

        $logger->error("DemEnrichmentJob request failed for model {$modelClass} with id {$modelId}", [
            'status' => $response->status(),
            'reason' => $response->reason(),
        ]);

        throw new Exception("DemEnrichmentJob request failed: {$response->status()} {$response->reason()}");
    }

    /**
     * Mark the model as having invalid geometry.
     */
    private function markModelAsInvalidGeometry(): void
    {
        DemEnrichment::updateOrCreate(
            [
                'enrichable_osmfeatures_id' => $this->model->getOsmfeaturesId(),
            ],
            [
                'dem-enrichable_id' => $this->model->id,
                'dem-enrichable_type' => get_class($this->model),
                'enrichable_has_invalid_geometry' => true,
            ]
        );
    }

    /**
     * Process a successful API response.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param LoggerInterface $logger
     */
    private function processSuccessfulResponse($response, LoggerInterface $logger): void
    {
        $data = $response->json();
        $logger->debug('DemEnrichmentJob response received');

        $this->saveDemEnrichment($data);
        $logger->debug('DemEnrichmentJob record created');
    }

    /**
     * Save the DEM enrichment data to the database.
     *
     * @param array $data
     */
    private function saveDemEnrichment(array $data): void
    {
        DemEnrichment::updateOrCreate(
            [
                'enrichable_osmfeatures_id' => $this->model->getOsmfeaturesId(),
            ],
            [
                'dem-enrichable_id' => $this->model->id,
                'dem-enrichable_type' => get_class($this->model),
                'data' => json_encode($data),
                'enrichable_has_invalid_geometry' => false,
            ]
        );
    }

    /**
     * Create a GeoJSON object from the model.
     *
     * @param Model $model
     * @param LoggerInterface $logger
     * @return array
     */
    private function createGeojsonFromModel(Model $model, LoggerInterface $logger): array
    {
        $modelTable = $model->getTable();

        $geometry = DB::select("SELECT ST_AsGeoJSON(geom) as geom FROM {$modelTable} WHERE id = ?", [$model->id]);

        $geojson = [
            'type' => 'Feature',
            'properties' => [
                'id' => $model->id,
            ],
            'geometry' => json_decode($geometry[0]->geom),
        ];

        return $geojson;
    }
}
