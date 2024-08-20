<?php

namespace App\Jobs;

use App\Models\AdminAreasEnrichment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateAdminAreasIntersectingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $model;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hr_geometry = $this->model->geom;

        if (empty($hr_geometry)) {
            Log::error("HR Geometry is empty or invalid.");
            return;
        }

        try {
            // Esegui la query per ottenere le admin areas intersecate
            $intersectingAdminAreas = DB::table('admin_areas')
                ->select('osm_type', 'osm_id', 'name', 'admin_level')
                ->whereRaw("ST_Intersects(geom, (SELECT geom FROM " . $this->model->getTable() . " WHERE id = ?))", [$this->model->id])
                ->get();


            // Organizza i risultati in base all'admin_level
            $admin_areas = [];
            foreach ($intersectingAdminAreas as $area) {
                $admin_level = $area->admin_level;
                if ($admin_level === null) {
                    continue;
                }
                if (!isset($admin_areas[$admin_level])) {
                    $admin_areas[$admin_level] = [];
                }

                $admin_areas[$admin_level][] = [
                    'osmfeatures_id' => $area->osm_type . $area->osm_id,
                    'name' => $area->name,
                ];
            }

            //crea un record di arricchimento admin areas
            AdminAreasEnrichment::updateOrCreate([
                'enrichable_osmfeatures_id' => $this->model->getOsmfeaturesId(),
            ], [
                'admin_areas-enrichable_id' => $this->model->id,
                'admin_areas-enrichable_type' => get_class($this->model),
                'data' => json_encode(['admin_area' => $admin_areas]),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in CalculateAdminAreasIntersectingJob: " . $e->getMessage());
        }
    }
}
