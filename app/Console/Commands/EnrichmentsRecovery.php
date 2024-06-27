<?php

namespace App\Console\Commands;

use App\Models\Enrichment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class EnrichmentsRecovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:enrichments-recovery {model=Place : The model where the enrichment should be recovered. Can be Place, AdminArea, HikingRoute or Pole}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reassign enrichments querying the osmfeatures_ids for each enrichment and re-link them to the related record with the new id.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $model = $this->argument('model');
        $modelClass = 'App\\Models\\' . $model;

        if (!class_exists($modelClass)) {
            $this->error("The model class $modelClass does not exist.");
            Log::error("The model class $modelClass does not exist.");
            return;
        }

        $enrichments = Enrichment::where('enrichable_type', $modelClass)->get();

        if ($enrichments->isEmpty()) {
            $this->info("No enrichments found for the model $modelClass.");
            return;
        }

        foreach ($enrichments as $enrichment) {
            try {
                $osmfeaturesId = $enrichment->enrichable_osmfeatures_id; // "N1234567" or "W1234567" or "R1234567"
                $osmType = substr($osmfeaturesId, 0, 1); // "N" or "W" or "R"
                $osmId = substr($osmfeaturesId, 1); // "1234567"

                $relatedModel = $modelClass::where('osm_type', $osmType)->where('osm_id', $osmId)->first();

                if ($relatedModel) {
                    $enrichment->enrichable_id = $relatedModel->id;
                    $enrichment->save();

                    $this->info('Enrichment recovered for ' . $modelClass . ' with osmfeatures ID ' . $osmfeaturesId);
                    Log::info('Enrichment recovered for ' . $modelClass . ' with osmfeatures ID ' . $osmfeaturesId);
                } else {
                    $this->warn('No related model found for enrichment with osmfeatures_id ' . $osmfeaturesId);
                    Log::warning('No related model found for enrichment with osmfeatures_id ' . $osmfeaturesId);
                }
            } catch (\Exception $e) {
                $this->error('Error recovering enrichment with enrichable_osmfeatures_id: ' . $osmfeaturesId . $e->getMessage());
                Log::error('Error recovering enrichment with enrichable_osmfeatures_id: ' . $osmfeaturesId . $e->getMessage());
            }
        }

        $this->info('Enrichments recovery process completed.');
        Log::info('Enrichments recovery process completed.');
    }
}