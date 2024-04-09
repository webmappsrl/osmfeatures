<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HikingRoutesCorrectTimestamp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:correct-hr-timestamps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correct the timestamps of hiking routes looping over all the members of the relation and setting the timestamp of the relation to the latest one.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hikingRoutes = DB::select('SELECT id, members, updated_at FROM hiking_routes WHERE members IS NOT NULL');
        $updateTime = now();

        foreach ($hikingRoutes as $hikingRoute) {
            $this->info('Processing hiking route ' . $hikingRoute->id);
            $members = json_decode($hikingRoute->members, true);

            $latestTimestamp = null;
            foreach ($members as $member) {
                if ($member['type'] === 'w') {
                    $osmApiUrl = 'https://api.openstreetmap.org/api/0.6/way/' . $member['ref'] . '.json';
                    try {
                        $osmData = json_decode(file_get_contents($osmApiUrl), true);
                        $timestamp = $osmData['elements'][0]['timestamp'];
                        //format the timestamp to iso8601
                        $timestamp = Carbon::parse($timestamp)->toIso8601String();
                    } catch (\Exception $e) {
                        $this->error('Error while fetching data from OSM API: ' . $e->getMessage());
                        continue;
                    }

                    if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                        $latestTimestamp = $timestamp;
                    }
                }
            }
            //update the updated_at column of the hiking routes with the latest timestamp if is more recent than the current one
            if ($latestTimestamp > $hikingRoute->updated_at) {
                DB::table('hiking_routes')
                    ->where('id', $hikingRoute->id)
                    ->update(['updated_at' => $latestTimestamp]);
                $this->info('Updated hiking route ' . $hikingRoute->id . ' with timestamp ' . $latestTimestamp);
            }
            $this->info('Hiking routes already up to date.');
        }

        $updateTime = now()->diffInSeconds() / 60;
        $this->info('All hiking routes have been processed in ' . $updateTime . ' minutes.');
        Log::info('All hiking routes have been processed in ' . $updateTime . ' minutes.');
    }
}
