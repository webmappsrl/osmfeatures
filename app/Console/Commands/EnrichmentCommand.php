<?php

namespace App\Console\Commands;

use App\Jobs\EnrichmentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class EnrichmentCommand extends Command
{
    protected $signature = 'osmfeatures:enrich {--only-media : Enrich only media} {model=Place: The model to enrich}{osmid?* : List of OSM IDs to enrich}';

    protected $description = 'Enrich provided osmfeatures model with data from Wikipedia, Wikidata, and Wikimedia Commons. Optionally provide a list of OSM IDs separated by space.';

    protected $usage = 'osmfeatures:enrich {model=Place: The model to enrich}{osmid?* : List of OSM IDs to enrich}';

    public function handle()
    {
        $onlyMedia = $this->option('only-media');
        try {
            $osmids = $this->argument('osmid');
            $jobCount = 0;
            $model = App::make('App\\Models\\' . $this->argument('model'));

            if (!empty($osmids)) {
                $modelsQuery = $model::whereIn('osm_id', $osmids);

                if ($modelsQuery->count() == 0) {
                    $this->info('No models found with the specified OSM IDs.');
                    return Command::FAILURE;
                }
            } else {
                $modelsQuery = $model::query();
            }

            // Process models in chunks
            $modelsQuery->chunk(100, function ($models) use ($onlyMedia, &$jobCount) {
                foreach ($models as $model) {
                    EnrichmentJob::dispatch($model, $onlyMedia);
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
