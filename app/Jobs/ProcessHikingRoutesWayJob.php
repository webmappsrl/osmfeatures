<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class ProcessHikingRoutesWayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, IsMonitored;

    protected $hikingRoutesWay;

    public function __construct($hikingRoutesWay)
    {
        $this->hikingRoutesWay = $hikingRoutesWay;
    }

    public function handle()
    {
        try {
            $hikingRoutes = DB::table('hiking_routes')->whereJsonContains('members', [['type' => 'w', 'ref' => $this->hikingRoutesWay->osm_id]])->get();

            foreach ($hikingRoutes as $hikingRoute) {
                // Compare the updated_at of the hiking_route and hiking_route_way and update if it is the most recent
                if ($this->hikingRoutesWay->updated_at > $hikingRoute->updated_at) {
                    if ($this->hikingRoutesWay->updated_at > $hikingRoute->updated_at_osm) {
                        DB::table('hiking_routes')
                            ->where('id', $hikingRoute->id)
                            ->update(['updated_at' => $this->hikingRoutesWay->updated_at]);
                    } else {
                        DB::table('hiking_routes')
                            ->where('id', $hikingRoute->id)
                            ->update(['updated_at' => $hikingRoute->updated_at_osm]);
                    }

                    Log::info('Updated hiking route: '.$hikingRoute->id.' with updated_at: '.$this->hikingRoutesWay->updated_at);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing hiking route way: '.$e->getMessage());
        }
    }
}
