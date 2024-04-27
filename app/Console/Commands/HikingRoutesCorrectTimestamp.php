<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessHikingRoutesWayJob;

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
        try {
            // Get the most recent last update from osm2pgsql_last_updates table in the imported_at column
            $lastUpdate = DB::table('osm2pgsql_crontab_updates')
                ->select(DB::raw('MAX(imported_at) as imported_at'))
                ->where('from_lua', '=', 'hiking_routes.lua')
                ->get();
            $lastUpdate = $lastUpdate[0]->imported_at;

            //subtract one day to the last update to avoid missing ways

            if ($lastUpdate != null) {
                //subtract one day to the last update
                $lastUpdate = Carbon::parse($lastUpdate[0]->imported_at)->subDay();
                $this->info('Last update: ' . $lastUpdate);
            } else {
                $this->info('No last update found');
            }

            $this->info('Selecting hiking routes ways updated after the last update...');

            // Query the database to get all the hiking_routes_ways that have the updated_at value that is more recent than the last update date
            if ($lastUpdate == null) {
                $hikingRoutesWays = DB::table('hiking_routes_ways')->get();
            } else {
                $hikingRoutesWays = DB::table('hiking_routes_ways')
                    ->where('updated_at', '>', $lastUpdate)
                    ->get();
            }

            // Create a new progress bar
            $bar = $this->output->createProgressBar(count($hikingRoutesWays));

            $this->info('Dispatching jobs...');

            // Dispatch a job for each hiking_routes_ways
            foreach ($hikingRoutesWays as $hikingRoutesWay) {
                //only dispatch jobs for ways that are part of a hiking route
                $hikingRoutes = DB::table('hiking_routes')->whereJsonContains('members', [['type' => 'w', 'ref' => $hikingRoutesWay->osm_id]])->get();
                if ($hikingRoutes->count() > 0) {
                    dispatch(new ProcessHikingRoutesWayJob($hikingRoutesWay));
                }
                // Advance the progress bar
                $bar->advance();
            }

            // Finish the progress bar
            $bar->finish();
            $this->info(''); // Add a new line after the progress bar (for better readability
            $this->info('Jobs dispatched successfully!');
        } catch (\Exception $e) {
            $this->error('Error in HikingRoutesCorrectTimestamp command: ' . $e->getMessage());
            Log::error('Error in HikingRoutesCorrectTimestamp command: ' . $e->getMessage());
        }
    }
}
