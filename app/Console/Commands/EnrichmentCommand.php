<?php

namespace App\Console\Commands;

use App\Jobs\EnrichmentJob;
use Illuminate\Console\Command;
use App\Models\Place;

class EnrichmentCommand extends Command
{
    protected $signature = 'osmfeatures:enrich';

    protected $description = 'Enrich osmfeatures places with data from Wikipedia, Wikidata, and Wikimedia Commons';

    public function handle()
    {
        $jobCount = 0;

        Place::chunk(100, function ($places) use (&$jobCount) {
            foreach ($places as $place) {
                EnrichmentJob::dispatch($place);
                $jobCount++;
            }
        });

        $this->info('Enrichment jobs dispatched successfully.');
        $this->info("Total jobs dispatched: $jobCount");

        return Command::SUCCESS;
    }
}
