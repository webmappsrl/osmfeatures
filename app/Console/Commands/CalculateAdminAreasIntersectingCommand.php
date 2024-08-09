<?php

namespace App\Console\Commands;

use App\Models\HikingRoute;
use Illuminate\Console\Command;
use App\Jobs\CalculateAdminAreasIntersectingJob;

class CalculateAdminAreasIntersectingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:calculate-admin-areas-intersecting {id? : The osmfeatures ID of the hiking route eg. R4174475}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the admin areas intersecting a given hiking route. If no ID is provided, all routes will be processed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching jobs...');
        $hikingRoutes = $this->argument('id') ? HikingRoute::getOsmfeaturesByOsmfeaturesID($this->argument('id')) : HikingRoute::all();

        if (!$hikingRoutes) {
            $this->newLine();
            $this->info('No routes found!');
            return;
        }

        //check if $hikingRoute is a collection
        if ($hikingRoutes instanceof \Illuminate\Support\Collection === false) {
            dispatch(new CalculateAdminAreasIntersectingJob($hikingRoutes));
        } else {
            $this->output->createProgressBar(count($hikingRoutes))->start();

            foreach ($hikingRoutes as $hikingRoute) {
                dispatch(new CalculateAdminAreasIntersectingJob($hikingRoute));
                $this->output->advance();
            }
            $this->output->finish();
        }
        $this->newLine();
        $this->info('Jobs dispatched successfully!');
    }
}
