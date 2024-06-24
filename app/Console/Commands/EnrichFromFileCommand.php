<?php

namespace App\Console\Commands;

use App\Jobs\EnrichmentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnrichFromFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:enrich-from-file {model} {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command allows to dispatch enrichment jobs for the given model using a list of osmfeatures id contained in the provided file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->argument('model');

        $path = $this->argument('path');

        //read the .txt file at the given path
        $this->info("Reading file $path");
        Log::info("Reading file $path");
        $file = file_get_contents($path);

        $this->info("Getting ids from file $path");
        $ids = explode("\n", $file);

        //clean the empty ids
        $ids = array_filter($ids, function ($id) {
            return !empty($id);
        });

        //trim initial spaces and final spaces
        $ids = array_map(function ($id) {
            return trim($id);
        }, $ids);

        $this->info("Found " . count($ids) . " ids in file $path");
        Log::info("Found " . count($ids) . " ids in file $path");

        //get the model class
        $modelClass = 'App\\Models\\' . $model;

        $this->info("Dispatching enrichment jobs...");
        Log::info("Dispatching enrichment jobs...");
        //get the model by ids and dispatch the jobs
        foreach ($ids as $id) {
            $model = $modelClass::getOsmfeaturesByOsmfeaturesID($id);
            if ($model) {
                dispatch(new EnrichmentJob($model));
                $this->info("Enrichment job dispatched for id $id");
            } else {
                Log::info("Model not found for id $id");
                $this->error("Model not found for id $id");
                continue;
            }
        }
    }
}