<?php

namespace App\Console\Commands;

use App\Jobs\EnrichmentJob;
use Illuminate\Console\Command;
use App\Models\Place;

class EnrichmentCommand extends Command
{
    protected $signature = 'osmfeatures:enrich {osmid?*}';

    protected $description = 'Enrich osmfeatures places with data from Wikipedia, Wikidata, and Wikimedia Commons. Optionally provide a list of OSM IDs separated by space.';

    public function handle()
    {
        try {
            $osmids = $this->argument('osmid');
            $jobCount = 0;


            if (!empty($osmids)) {
                $places = Place::whereIn('osm_id', $osmids);

                if ($places->count() == 0) {
                    $this->info('No places found with the specified OSM IDs.');
                    return Command::FAILURE;
                }
            } else {
                $places = Place::all();
            }

            $places->chunk(100, function ($places) use (&$jobCount) {
                foreach ($places as $place) {
                    EnrichmentJob::dispatch($place);
                    $jobCount++;
                }
            });

            $this->info('Enrichment jobs dispatched successfully.');
            $this->info("Total jobs dispatched: $jobCount");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
