<?php

namespace App\Console\Commands;

use App\Jobs\CalculateAdminAreasIntersectingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\AdminAreasEnrichment;

class CheckAdminAreasIntersectngUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:check-admin-areas-intersecting-update {model : The name of the model} {id? : The osmfeatures ID of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command loops over all admin areas intersecting enrichment records in database and compares them to the related model updated_at value (if osmfeatures id not provided). If the model updated_at value is greater than the admin areas enrichments record updated_at value, the admin areas enrichments record will be updated making another call to the API.';

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
        if ($this->argument('id')) {
            $adminAreasEnrichments = collect(AdminAreasEnrichment::where('enrichable_osmfeatures_id', $this->argument('id'))->get());
        } else {
            $adminAreasEnrichments = AdminAreasEnrichment::all();
        }

        $logger->info('Checking admin areas enrichments update...');
        $progressBar = $this->output->createProgressBar(count($adminAreasEnrichments));
        foreach ($adminAreasEnrichments as $enrichment) {
            $progressBar->advance();
            $enrichmentTimestamp = $enrichment->updated_at;
            $modelInstance = $modelClass::getOsmfeaturesByOsmfeaturesID($enrichment->enrichable_osmfeatures_id);
            $modelTimestamp = $modelInstance->updated_at;
            //if the enrichment is outdated perform an admin areas enrichment job on the related model
            if ($modelTimestamp > $enrichmentTimestamp) {
                $logger->info('Enrichment ' . $enrichment->id . ' is outdated. Dispatching job for Enrich model ' . get_class($enrichment->adminEnrichable) . ' ' . $enrichment->adminEnrichable->osm_type . $enrichment->adminEnrichable->osm_id);
                CalculateAdminAreasIntersectingJob::dispatch($enrichment->adminAreasEnrichable);
            }
        }

        $progressBar->finish();
        $this->newLine();
        $logger->info('Finished checking admin areas enrichments update');
    }
}
