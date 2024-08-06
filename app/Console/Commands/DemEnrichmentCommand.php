<?php

namespace App\Console\Commands;

use App\Jobs\DemEnrichmentJob;
use Illuminate\Console\Command;

class DemEnrichmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:dem-enrichment {model : The model class eg: HikingRoute} {id? : The Osmfeatures ID eg: R9758811}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Launch dem enrichments jobs for the given model and Osmfeatures ID. If no ID is provided, all Osmfeatures will be processed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $osmfeaturesId = $this->argument('id');
        $model = $this->argument('model');

        if ($osmfeaturesId) {
            $modelClass = 'App\\Models\\' . $model;

            if (!class_exists($modelClass)) {
                $this->error("The model class $modelClass does not exist.");
                return;
            }

            $relatedModel = $modelClass::getOsmfeaturesByOsmfeaturesID($osmfeaturesId);
            DemEnrichmentJob::dispatch($relatedModel);
        } else {
            $relatedModels = $model::all();
            foreach ($relatedModels as $model) {
                DemEnrichmentJob::dispatch($model);
            }
        }
    }
}