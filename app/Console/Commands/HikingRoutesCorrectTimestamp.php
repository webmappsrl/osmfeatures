<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\HikingRoute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessHikingRoutesJob;
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
            $hikingRoutes = HikingRoute::all('id', 'updated_at_osm', 'updated_at');

            $this->info('Dispatching jobs...');
            $bar = $this->output->createProgressBar(count($hikingRoutes));

            foreach ($hikingRoutes as $route) {
                dispatch(new ProcessHikingRoutesJob($route));
                $bar->advance();
            }

            $bar->finish();
            $this->info(''); // Add a new line after the progress bar (for better readability
            $this->info('Jobs dispatched successfully!');
        } catch (\Exception $e) {
            $this->error('Error in HikingRoutesCorrectTimestamp command: ' . $e->getMessage());
            Log::error('Error in HikingRoutesCorrectTimestamp command: ' . $e->getMessage());
        }
    }
}
