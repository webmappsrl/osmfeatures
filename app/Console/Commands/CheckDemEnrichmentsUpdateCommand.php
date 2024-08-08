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
    protected $signature = 'osmfeatures:check-dem-enrichments-update {model : The name of the model} {id? : The osmfeatures ID of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command loop over all dem enrichments record in database and compare them to the related model updated_at value (if osmfeatures id not provided). If the model updated_at value is greater than the dem enrichments record updated_at value, the dem enrichments record will be updated making another call to dem api.';

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
        if ($this->argument('id')) {
            $demEnrichments = collect(DemEnrichment::where('enrichable_osmfeatures_id', $this->argument('id'))->get());
        } else {
            $demEnrichments = DemEnrichment::all();
        }

        $logger->info('Checking dem enrichments update...');
        $progressBar = $this->output->createProgressBar(count($demEnrichments));
        foreach ($demEnrichments as $enrichment) {
            $progressBar->advance();
            $enrichmentTimestamp = $enrichment->updated_at;
            $modelInstance = $modelClass::getOsmfeaturesByOsmfeaturesID($enrichment->enrichable_osmfeatures_id);
            $modelTimestamp = $modelInstance->updated_at;
            //if the enrichment is outdated perform a dem enrichment job on the related model
            if ($modelTimestamp > $enrichmentTimestamp) {
                $logger->info('Enrichment ' . $enrichment->id . ' is outdated. Dispatching job for Enrich model ' . get_class($enrichment->demEnrichable) . ' ' . $enrichment->demEnrichable->osm_type . $enrichment->demEnrichable->osm_id);
                DemEnrichmentJob::dispatch($enrichment->demEnrichable);
            }
        }

        $progressBar->finish();
        $this->newLine();
        $logger->info('Finished checking dem enrichments update');
    }
}
