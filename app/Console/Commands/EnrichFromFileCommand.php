<?php

namespace App\Console\Commands;

use App\Jobs\EnrichmentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputArgument;

class EnrichFromFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:enrich-from-file {model=Place : The name of the model} {path=storage/ec_pois.txt : The path to the .txt file containing osmfeatures IDs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Dispatch enrichment jobs for the given model using a list of osmfeatures IDs contained in the provided file. Example: 'php artisan osmfeatures:enrich-from-file Place storage/ec_pois.txt'";

    /**
     * The console command usage example.
     *
     * @var string
     */
    protected $usage = 'Usage:
    php artisan osmfeatures:enrich-from-file {model} {path}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->argument('model');
        $path = $this->argument('path');

        // Validate the path
        if (!file_exists($path) || !is_readable($path)) {
            $this->error("The file at path $path does not exist or is not readable.");
            return 1;
        }

        // Read the .txt file at the given path
        $this->info("Reading file $path");
        Log::info("Reading file $path");
        $file = file_get_contents($path);

        $this->info("Getting ids from file $path");
        $ids = explode("\n", $file);

        // Clean the empty ids
        $ids = array_filter($ids, function ($id) {
            return !empty($id);
        });

        // Trim initial spaces and final spaces
        $ids = array_map(function ($id) {
            return trim($id);
        }, $ids);

        $this->info("Found " . count($ids) . " ids in file $path");
        Log::info("Found " . count($ids) . " ids in file $path");

        // Get the model class
        try {
            $modelClass = App::make('App\\Models\\' . $model);
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            $this->error("The model class App\\Models\\$model does not exist.");
            return 1;
        }

        if (!$modelClass) {
            $this->error("The model class $modelClass does not exist.");
            return 1;
        }

        $this->info("Dispatching enrichment jobs...");
        Log::info("Dispatching enrichment jobs...");

        // Get the model by ids and dispatch the jobs
        foreach ($ids as $id) {
            $modelInstance = $modelClass::getOsmfeaturesByOsmfeaturesID($id);
            if ($modelInstance) {
                dispatch(new EnrichmentJob($modelInstance));
                $this->info("Enrichment job dispatched for id $id");
            } else {
                Log::info("Model not found for id $id");
                $this->error("Model not found for id $id");
                continue;
            }
        }

        return 0;
    }


    /**
     * Get the console command usage examples.
     *
     * @return string
     */
    protected function getUsage()
    {
        return $this->usage;
    }
}
