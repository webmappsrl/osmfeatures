<?php

namespace App\Console\Commands;

use App\Models\DemEnrichment;
use App\Models\Enrichment;
use App\Models\AdminAreasEnrichment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnrichmentsRecovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:enrichments-recovery 
                            {model=Place : The model where the enrichment should be recovered. Can be Place, AdminArea, HikingRoute or Pole} 
                            {--dem : Run the command on DEM enrichments} 
                            {--admin-areas : Run the command on Admin Areas enrichments} 
                            {--wiki : Run the command on AI enrichments}';

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

        // Determina quali arricchimenti devono essere recuperati
        $runDem = $this->option('dem');
        $runAdminAreas = $this->option('admin-areas');
        $runWiki = $this->option('wiki');

        // Se nessuna opzione è definita, esegui il recovery su tutti gli arricchimenti
        if (!$runDem && !$runAdminAreas && !$runWiki) {
            $runDem = $runAdminAreas = $runWiki = true;
        }

        // Processa ogni tipo di arricchimento
        if ($runDem) {
            $this->processEnrichments(DemEnrichment::class, $modelClass, 'dem-enrichable_type', 'dem-enrichable_id');
        }

        if ($runAdminAreas) {
            $this->processEnrichments(AdminAreasEnrichment::class, $modelClass, 'admin_areas-enrichable_type', 'admin_areas-enrichable_id');
        }

        if ($runWiki) {
            $this->processEnrichments(Enrichment::class, $modelClass, 'enrichable_type', 'enrichable_id');
        }

        $this->info('Enrichments recovery process completed.');
        Log::info('Enrichments recovery process completed.');
    }

    /**
     * Process the enrichment recovery for a given enrichment type and model class.
     *
     * @param string $enrichmentClass
     * @param string $modelClass
     * @param string $enrichableTypeField
     * @param string $enrichableIdField
     * @return void
     */
    protected function processEnrichments($enrichmentClass, $modelClass, $enrichableTypeField, $enrichableIdField)
    {
        $enrichments = $enrichmentClass::where($enrichableTypeField, $modelClass)->get();

        if ($enrichments->isEmpty()) {
            $this->info("No enrichments found for the model $modelClass in $enrichmentClass.");
            return;
        }

        foreach ($enrichments as $enrichment) {
            try {
                $osmfeaturesId = $enrichment->enrichable_osmfeatures_id; // "N1234567" or "W1234567" or "R1234567"

                $relatedModel = $modelClass::getOsmfeaturesByOsmfeaturesID($osmfeaturesId);

                if ($relatedModel) {
                    $enrichment->{$enrichableIdField} = $relatedModel->id;
                    $enrichment->save();

                    $this->info("Enrichment recovered for $modelClass with osmfeatures ID $osmfeaturesId in $enrichmentClass.");
                    Log::info("Enrichment recovered for $modelClass with osmfeatures ID $osmfeaturesId in $enrichmentClass.");
                } else {
                    $this->warn("No related model found for enrichment with osmfeatures_id $osmfeaturesId in $enrichmentClass.");
                    Log::warning("No related model found for enrichment with osmfeatures_id $osmfeaturesId in $enrichmentClass.");
                }
            } catch (\Exception $e) {
                $this->error("Error recovering enrichment with enrichable_osmfeatures_id $osmfeaturesId in $enrichmentClass: " . $e->getMessage());
                Log::error("Error recovering enrichment with enrichable_osmfeatures_id $osmfeaturesId in $enrichmentClass: " . $e->getMessage());
            }
        }
    }
}
