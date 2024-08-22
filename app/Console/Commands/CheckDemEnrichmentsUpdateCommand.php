<?php

namespace App\Console\Commands;

use App\Jobs\DemEnrichmentJob;
use App\Models\DemEnrichment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDemEnrichmentsUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:check-dem-enrichments-update
                            {model=HikingRoute : The name of the model}
                            {id? : The osmfeatures ID of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and updates DEM enrichment records and handles missing enrichments.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logger = Log::channel('dem-enrichment');
        $modelClass = 'App\\Models\\' . $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error("The model class $modelClass does not exist.");
            $logger->error("The model class $modelClass does not exist.");
            return;
        }

        // Recupera gli arricchimenti esistenti
        $demEnrichments = $this->getDemEnrichments();

        $logger->info('Checking DEM enrichments update...');
        $progressBar = $this->output->createProgressBar(count($demEnrichments));

        // Controlla e aggiorna gli arricchimenti esistenti
        foreach ($demEnrichments as $enrichment) {
            $progressBar->advance();
            $this->updateEnrichmentIfOutdated($enrichment, $modelClass, $logger);
        }

        $progressBar->finish();
        $this->newLine();

        // Gestisce i record senza arricchimento associato
        if (!$this->argument('id')) {
            $this->processMissingEnrichments($modelClass, $logger);
        }

        $logger->info('Finished checking DEM enrichments update.');
    }

    /**
     * Recupera gli arricchimenti DEM esistenti dal database.
     */
    protected function getDemEnrichments()
    {
        if ($this->argument('id')) {
            return DemEnrichment::where('enrichable_osmfeatures_id', $this->argument('id'))->get();
        } else {
            return DemEnrichment::all();
        }
    }

    /**
     * Aggiorna l'arricchimento DEM se è obsoleto rispetto al modello collegato.
     */
    protected function updateEnrichmentIfOutdated($enrichment, $modelClass, $logger)
    {
        $enrichmentTimestamp = $enrichment->updated_at;
        $modelInstance = $modelClass::getOsmfeaturesByOsmfeaturesID($enrichment->enrichable_osmfeatures_id);

        if (!$modelInstance) {
            $this->error('No model ' . $modelClass . ' found with osmfeatures ID: ' . $enrichment->enrichable_osmfeatures_id);
            $logger->error('No model ' . $modelClass . ' found with osmfeatures ID: ' . $enrichment->enrichable_osmfeatures_id);
            return;
        }

        $modelTimestamp = $modelInstance->updated_at;

        // Se l'arricchimento è obsoleto, aggiorna
        if ($modelTimestamp > $enrichmentTimestamp) {
            $logger->info('Enrichment ' . $enrichment->id . ' is outdated. Dispatching job for Enrich model '
                . get_class($modelInstance) . ' ' . $modelInstance->osm_type . $modelInstance->osm_id);
            DemEnrichmentJob::dispatch($modelInstance);
        }
    }

    /**
     * Trova i record senza arricchimento associato e lancia il job di arricchimento.
     */
    protected function processMissingEnrichments($modelClass, $logger)
    {
        $logger->info('Processing missing enrichments...');
        $this->info('Processing missing enrichments...');

        // Trova i record che non hanno arricchimenti associati
        $missingEnrichments = $modelClass::whereDoesntHave('demEnrichment')->get();

        if ($missingEnrichments->isEmpty()) {
            $logger->info('No missing enrichments found.');
            return;
        }

        foreach ($missingEnrichments as $model) {
            $logger->info('Dispatching job for missing enrichment on model ' . get_class($model) . ' ' . $model->osm_type .
                $model->osm_id);
            $this->info('Enrichment ' . $model->osm_type . $model->osm_id . ' is missing. Dispatching job for Enrich model ' . get_class($model) . ' ' . $model->osm_type . $model->osm_id);
            DemEnrichmentJob::dispatch($model);
        }
    }
}
