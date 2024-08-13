<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class UpdateOsmPbf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:osm2pgsql-replication {pbf?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute the update of the OSM PBF file using osm2pgsql-replication. If the init is not executed yet, it will also initialize the database for the replication process. Use the pbf argument to specify the name of the PBF file to use e.g. osmfeatures:update-osm-pbf italy_latest';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->argument('pbf')) {
            $pbf = $this->argument('pbf');
        } else {
            $pbf = text(
                label: 'Name of the PBF file to use',
                placeholder: 'italy_latest.pbf',
                hint: 'The file must be saved in storage/osm/pbf/ with the specified name.',
                required: true,
                default: 'italy_latest',
            );
        }

        $pbfPath = storage_path('osm/pbf/original_' . $pbf . '.pbf');
        $dbName = env('DB_DATABASE', 'osmfeatures');
        $dbUser = env('DB_USERNAME', 'osmfeatures');
        $dbPassword = env('DB_PASSWORD', 'osmfeatures');
        $luaFile = storage_path('osm/lua/all_imports_3857.lua');

        //check if pbf_file name exists in storage/osm/pbf
        if (! file_exists($pbfPath)) {
            $existingLuaFiles = array_map('basename', glob(storage_path('osm/pbf') . '/*.pbf'));
            $this->error('The file ' . $pbf . ' does not exist in storage/osm/lua. Existing pbf files: ' . implode(', ', $existingLuaFiles));

            return;
        }

        //osm2pgsql-replication init command only if the init has not already been launched
        $this->info('Executing osm2pgsql-replication init');
        if (! Schema::hasTable('planet_osm_replication_status')) {
            $this->info('The database has not been initialized yet, launching osm2pgsql-replication init');
            Log::info('The database has not been initialized yet, launching osm2pgsql-replication init');
            $this->osm2pgsqlReplicationInit($dbPassword, $dbName, $dbUser, $pbfPath);
        } else {
            $this->info('The osm2pgsql-replication init has already been launched');
            Log::info('The osm2pgsql-replication init has already been launched');
        }
        Log::info('Executing osm2pgsql-replication update');
        $this->info('Executing osm2pgsql-replication update');
        $this->osm2pgsqlReplicationUpdate($dbPassword, $dbName, $dbUser, $luaFile);

        Log::info('OSM PBF file updated successfully');
        $this->info('OSM PBF file updated successfully');
    }

    /**
     * Initializes the osm2pgsql replication process for the given database and PBF file.
     *
     * @param string $dbPassword The password for the database.
     * @param string $dbName The name of the database.
     * @param string $dbUser The user for the database.
     * @param string $pbfPath The path to the PBF file.
     * @return void
     */
    protected function osm2pgsqlReplicationInit(string $dbPassword, string $dbName, string $dbUser, string $pbfPath)
    {
        // Construct the osm2pgsql-replication init command
        $osm2pgsqlInit = "PGPASSWORD=$dbPassword osm2pgsql-replication init -d $dbName -H 'db' -U $dbUser --osm-file $pbfPath";

        // Execute the osm2pgsql-replication init command
        $this->info('Executing osm2pgsql-replication init');
        exec($osm2pgsqlInit, $output, $return_var);

        // If the command failed, display an error message and return
        if ($return_var !== 0) {
            $this->error('Error executing osm2pgsql-replication init');

            return;
        }
    }

    /**
     * Executes the osm2pgsql-replication update command.
     *
     * @param string $dbPassword The password for the database.
     * @param string $dbName The name of the database.
     * @param string $dbUser The user for the database.
     * @param string $luaFile The path to the Lua file.
     * @return void
     */
    protected function osm2pgsqlReplicationUpdate(string $dbPassword, string $dbName, string $dbUser, string $luaFile)
    {
        // osm2pgsql-replication update command
        $osm2pgsqlUpdate = "PGPASSWORD=$dbPassword osm2pgsql-replication update -d $dbName -H 'db' -U $dbUser -- -O flex -x -S $luaFile";

        // execute osm2pgsql-replication update
        $this->info('Executing osm2pgsql-replication update for ' . $luaFile);
        exec($osm2pgsqlUpdate, $output, $return_var);

        // if the command failed, display an error message and return
        if ($return_var !== 0) {
            $this->error('Error executing osm2pgsql-replication update');

            return;
        }
    }
}