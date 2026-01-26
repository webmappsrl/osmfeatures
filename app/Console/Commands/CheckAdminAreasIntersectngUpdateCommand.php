<?php

namespace App\Console\Commands;

use App\Jobs\CalculateAdminAreasIntersectingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\AdminAreasEnrichment;
use App\Models\HikingRoute;

class CheckAdminAreasIntersectngUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:check-admin-areas-intersecting-update
                            {model=HikingRoute : The name of the model}
                            {id? : The osmfeatures ID of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and updates admin areas enrichment records and handles missing enrichments for hiking routes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logger = Log::channel('admin-areas-enrichment');
        $modelClass = 'App\\Models\\' . $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error("The model class $modelClass does not exist.");
            $logger->error("The model class $modelClass does not exist.");
            return;
        }

        // Recupera gli arricchimenti esistenti
        $adminAreasEnrichments = $this->getAdminAreasEnrichments();

        $logger->info('Checking admin areas enrichments update...');
        $progressBar = $this->output->createProgressBar(count($adminAreasEnrichments));

        // Controlla e aggiorna gli arricchimenti esistenti
        foreach ($adminAreasEnrichments as $enrichment) {
            $progressBar->advance();
            $this->updateEnrichmentIfOutdated($enrichment, $modelClass, $logger);
        }

        $progressBar->finish();
        $this->newLine();

        // Gestisce i record senza arricchimento associato
        if (!$this->argument('id')) {
            $this->processMissingEnrichments($modelClass, $logger);
        }

        $logger->info('Finished checking admin areas enrichments update.');
    }

    /**
     * Recupera gli arricchimenti esistenti dal database.
     */
    protected function getAdminAreasEnrichments()
    {
        if ($this->argument('id')) {
            return AdminAreasEnrichment::where('enrichable_osmfeatures_id', $this->argument('id'))->get();
        } else {
            return AdminAreasEnrichment::all();
        }
    }

    /**
     * Aggiorna l'arricchimento se è obsoleto rispetto al modello collegato.
     */
    protected function updateEnrichmentIfOutdated($enrichment, $modelClass, $logger)
    {
        $enrichmentTimestamp = $enrichment->updated_at;
        $modelInstance = $modelClass::getOsmfeaturesByOsmfeaturesID($enrichment->enrichable_osmfeatures_id);
        $modelTimestamp = $modelInstance->updated_at;

        // Se l'arricchimento è obsoleto, aggiorna
        if ($modelTimestamp > $enrichmentTimestamp) {
            $logger->info('Enrichment ' . $enrichment->id . ' is outdated. Dispatching job for Enrich model '
                . get_class($enrichment->adminAreasEnrichable) . ' ' . $enrichment->adminAreasEnrichable->osm_type
                . $enrichment->adminAreasEnrichable->osm_id);
            CalculateAdminAreasIntersectingJob::dispatch($enrichment->adminAreasEnrichable);
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
        $missingEnrichments = $modelClass::whereDoesntHave('adminAreasEnrichment')->get();

        if ($missingEnrichments->isEmpty()) {
            $logger->info('No missing enrichments found.');
            return;
        }

        foreach ($missingEnrichments as $model) {
            $logger->info('Dispatching job for missing enrichment on model ' . get_class($model) . ' ' . $model->osm_type
                . $model->osm_id);
            $this->info('Enrichment ' . $model->osm_type . $model->osm_id . ' is missing. Dispatching job for Enrich model ' . get_class($model) . ' ' . $model->osm_type . $model->osm_id);
            CalculateAdminAreasIntersectingJob::dispatch($model);
        }
    }
}
