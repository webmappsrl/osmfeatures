<?php

namespace App\Console\Commands;

use App\Jobs\CalculateAdminAreasIntersectingJob;
use App\Models\AdminAreasEnrichment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class checkAdminAreasEnrichmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:check-admin-areas-enrichments-update {model : The name of the model} {id? : The osmfeatures ID of the model}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command loop over all admin areas enrichments record in database and compare them to the related model updated_at value (if osmfeatures id not provided). If the model updated_at value is greater than the enrichment record updated_at value, the enrichment record will be updated.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelClass = 'App\\Models\\' . $this->argument('model');
        if (!class_exists($modelClass)) {
            $this->error("The model class $modelClass does not exist.");
            $logger->error("The model class $modelClass does not exist.");
            return;
        }
        if ($this->argument('id')) {
            $enrichments = collect(AdminAreasEnrichment::where('enrichable_osmfeatures_id', $this->argument('id'))->get());
        } else {
            $enrichments = AdminAreasEnrichment::all();
        }

        Log::info('Checking admin areas enrichments update');
        $progressBar = $this->output->createProgressBar($enrichments->count());
        foreach ($enrichments as $enrichment) {
            $progressBar->advance();
            $enrichmentTimestamp = $enrichment->updated_at;
            $modelInstance = $modelClass::getOsmfeaturesByOsmfeaturesID($enrichment->enrichable_osmfeatures_id);
            if (!$modelInstance) {
                $this->error('No model ' . $modelClass . ' found with osmfeatured id of: ' . $enrichment->enrichable_osmfeatures_id);
                Log::error('No model ' . $modelClass . ' found with osmfeatured id of: ' . $enrichment->enrichable_osmfeatures_id);
                continue;
            }
            $modelTimestamp = $modelInstance->updated_at;
            //if the enrichment is outdated perform an admin areas enrichment job on the related model
            if ($modelTimestamp > $enrichmentTimestamp) {
                Log::info('Enrichment ' . $enrichment->id . ' is outdated. Dispatching job for Enrich model ' . get_class($modelInstance) . ' ' . $modelInstance->osm_type . $modelInstance->osm_id);
                CalculateAdminAreasIntersectingJob::dispatch($modelInstance);
            }
        }

        $progressBar->finish();
        $this->info('Done!');
        Log::info('Done!');
    }
}
