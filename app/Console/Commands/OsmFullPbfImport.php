<?php

namespace App\Console\Commands;

use App\Services\Osm2pgsqlService;
use Exception;

class OsmFullPbfImport extends PbfUpdate
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:osm-import 
        {pbf? : Specify the filename to use only without the .pbf extension, eg: italy_latest}
        {--remove-pbf : Remove the PBF file after the import}
        {--force-download : Force the download of the PBF file}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the database with osm features downloading a new pbf with low application downtime. Eg: php artisan osmfeatures:osm-import italy-latest.osm --force-download';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $osm2pgsqlService = Osm2pgsqlService::make();
        $luaPath = storage_path('osm/lua/all_imports.lua');


        [$pbfUrl, $pbfPath] = $this->getPbfUrlAndPath();


        if ($this->option('force-download') || ! file_exists($pbfPath)) {
            if (is_null($pbfUrl))
                throw new Exception("PBF file not found: $pbfPath. Impossible to download a new one.");
            $this->handleDownload($pbfUrl, $pbfPath);
        } else {
            $this->logToConsoleAndFile("Using existing PBF file: $pbfPath");
        }


        $osm2pgsqlService->import($luaPath, $pbfPath);
        $this->info("Database updated with the latest OSM features.");

        if ($this->option('remove-pbf')) {
            unlink($pbfPath);
            $this->logToConsoleAndFile("Removed PBF file: $pbfPath");
        }
    }


    // /**
    //  * Initializes the osm2pgsql replication process for the given database and PBF file.
    //  *
    //  * @param string $dbPassword The password for the database.
    //  * @param string $dbName The name of the database.
    //  * @param string $dbUser The user for the database.
    //  * @param string $pbfPath The path to the PBF file.
    //  * @return void
    //  */
    // protected function osm2pgsqlReplicationInit(string $dbPassword, string $dbName, string $dbUser, string $pbfPath)
    // {
    //     // Construct the osm2pgsql-replication init command
    //     $command = "PGPASSWORD=$dbPassword osm2pgsql-replication init -d $dbName -H 'db' -U $dbUser --osm-file $pbfPath";

    //     // Execute the osm2pgsql-replication init command
    //     $this->exec($command);
    // }

    // protected function osmiumDeriveChanges($oldPbfPath, $newPbfPath)
    // {
    //     $diffDestinationPath = storage_path($this::pbfDir . 'diff.osc');
    //     $command = "osmium derive-changes \"{$oldPbfPath}\" \"{$newPbfPath}\" -o \"{$diffDestinationPath}\"";
    //     $this->exec($command);
    //     return $diffDestinationPath;
    // }

    // /**
    //  * Executes the osm2pgsql-replication update command.
    //  *
    //  * @param string $dbPassword The password for the database.
    //  * @param string $dbName The name of the database.
    //  * @param string $dbUser The user for the database.
    //  * @param string $luaFile The path to the Lua file.
    //  * @return void
    //  */
    // protected function osm2pgsqlReplicationUpdate(string $dbPassword, string $dbName, string $dbUser, string $luaFile)
    // {
    //     // osm2pgsql-replication update command
    //     // add --log-level=debug to get more information
    //     $command = "PGPASSWORD=$dbPassword osm2pgsql-replication update -d $dbName -H 'db' -U $dbUser -- -O flex -x -S $luaFile";

    //     // execute osm2pgsql-replication update
    //     $this->exec($command);
    // }


    // /**
    //  * Runs the osm2pgsql command with the append option.
    //  *
    //  * @param string $dbPassword The password for the database.
    //  * @param string $dbName The name of the database.
    //  * @param string $dbUser The user for the database.
    //  * @param string $changesFilePath The path of the changes file
    //  * @param string $luaFile The path to the Lua file.
    //  * @return void
    //  */
    // protected function osm2pgsqlAppend(string $dbPassword, string $dbName, string $dbUser, string $changesFilePath, string $luaFile)
    // {
    //     // Construct the osm2pgsql command
    //     $command = "PGPASSWORD=$dbPassword osm2pgsql --append --slim -d $dbName -H 'db' -U $dbUser -O flex -x -S $luaFile $changesFilePath";

    //     // Execute the osm2pgsql command
    //     $this->exec($command);
    // }

    // protected function exec($command): bool
    // {
    //     $this->logToConsoleAndFile("Executing: $command");
    //     exec($command, $output, $return_var);

    //     // If the command failed, display an error message and return
    //     if ($return_var !== 0) {
    //         $outputString = implode(PHP_EOL, $output);
    //         $this->logToConsoleAndFile("Error executing \"$command\" command: $outputString", 'error');

    //         return false;
    //     }

    //     return true;
    // }
}
