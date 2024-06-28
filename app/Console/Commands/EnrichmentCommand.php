<?php

namespace App\Console\Commands;

use App\Jobs\EnrichmentJob;
use Illuminate\Console\Command;
use App\Models\Place;
use Illuminate\Support\Facades\App;

class EnrichmentCommand extends Command
{
    protected $signature = 'osmfeatures:enrich {model=Place: The model to enrich}{osmid?* : List of OSM IDs to enrich}';

    protected $description = 'Enrich provided osmfeatures model with data from Wikipedia, Wikidata, and Wikimedia Commons. Optionally provide a list of OSM IDs separated by space.';

    protected $usage = 'osmfeatures:enrich {model=Place: The model to enrich}{osmid?* : List of OSM IDs to enrich}';

    public function handle()
    {
        try {
            $osmids = $this->argument('osmid');
            $jobCount = 0;
            $model = App::make('App\\Models\\' . $this->argument('model'));


            if (!empty($osmids)) {
                $models = $model::whereIn('osm_id', $osmids);

                if ($models->count() == 0) {
                    $this->info('No models found with the specified OSM IDs.');
                    return Command::FAILURE;
                }
            } else {
                $models = $model::all();
            }

            $models->chunk(100, function ($models) use (&$jobCount) {
                foreach ($models as $model) {
                    EnrichmentJob::dispatch($model);
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
