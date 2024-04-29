<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessHikingRoutesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $hikingRoute;

    /**
     * Create a new job instance.
     */
    public function __construct($hikingRoute)
    {
        $this->hikingRoute = $hikingRoute;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = "SELECT MAX(updated_at)
            FROM hiking_routes_ways
            WHERE osm_id IN (
                SELECT UNNEST(STRING_TO_ARRAY(
                    (SELECT members_ids FROM hiking_routes WHERE id = '{$this->hikingRoute->id}'),
                    ','
                )::BIGINT[])
            );";

        $maxUpdatedAt = DB::select($query)[0]->max;

        //if $maxUpdatedAt is more recent than $route->updated_at and $route->updated_at_osm then update $route->updated_at
        if ($maxUpdatedAt > $this->hikingRoute->updated_at && $maxUpdatedAt > $this->hikingRoute->updated_at_osm) {
            $this->hikingRoute->updated_at = $maxUpdatedAt;
            $this->hikingRoute->save();
        } else {
            $this->hikingRoute->updated_at = $this->hikingRoute->updated_at_osm;
            $this->hikingRoute->save();
        }
    }
}
