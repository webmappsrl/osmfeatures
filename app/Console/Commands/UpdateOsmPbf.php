<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class UpdateOsmPbf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:update-osm-pbf {pbf_file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exectute the update of the OSM PBF file using osm2pgsql-replication';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pbf = $this->argument('pbf_file');
        $pbfPath = storage_path('osm/pbf/original_' . $pbf . '.pbf');
        $dbName = env('DB_DATABASE', 'osmfeatures');
        $dbUser = env('DB_USERNAME', 'osmfeatures');
        $dbPassword = env('DB_PASSWORD', 'osmfeatures');

        //check if pbf_file name exists in storage/osm/lua 
        if (!file_exists($pbfPath)) {
            $this->error('The file ' . $pbf . ' does not exist in storage/osm/lua');
            return;
        }

        //osm2pgsql-replication init command
        $osm2pgsqlInit = "PGPASSWORD=$dbPassword osm2pgsql-replication init -d $dbName -H 'db' -U $dbUser --osm-file $pbfPath";

        //get all the lua files in storage/osm/lua and loop through them
        $luaFiles = glob(storage_path('osm/lua/*.lua'));
        foreach ($luaFiles as $index => $luaFile) {
            //execute osm2pgsql-replication init
            $this->info('Executing osm2pgsql-replication init');
            exec($osm2pgsqlInit, $output, $return_var);
            if ($return_var !== 0) {
                $this->error('Error executing osm2pgsql-replication init');
                return;
            }
            //osm2pgsql-replication update command
            $osm2pgsqlUpdate = "PGPASSWORD=$dbPassword osm2pgsql-replication update -d $dbName -H 'db' -U $dbUser -- -O flex -x -S $luaFile";
            //execute osm2pgsql-replication update
            $this->info('Executing osm2pgsql-replication update for ' . $luaFile);
            exec($osm2pgsqlUpdate, $output, $return_var);
            if ($return_var !== 0) {
                $this->error('Error executing osm2pgsql-replication update');
                return;
            }
        }
        $this->info('OSM PBF file updated successfully');
    }
}
