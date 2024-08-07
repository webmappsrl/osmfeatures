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
    protected $signature = 'osmfeatures:check-dem-enrichments-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command loop over all dem enrichments record in database and compare them to the related model updated_at value. If the model updated_at value is greater than the dem enrichments record updated_at value, the dem enrichments record will be updated making another call to dem api.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logger = Log::channel('dem-enrichment');

        $logger->info('Checking dem enrichments update...');
        $demEnrichments = DemEnrichment::all();
        $progressBar = $this->output->createProgressBar(count($demEnrichments));
        foreach ($demEnrichments as $enrichment) {
            $progressBar->advance();
            $enrichmentTimestamp = $enrichment->updated_at;
            $modelTimestamp = $enrichment->demEnrichable->updated_at;
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
