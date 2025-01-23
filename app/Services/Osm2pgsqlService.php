<?php



namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

class Osm2pgsqlService
{

    protected static $slaveDbConnection = 'osm2pgsql';

    protected static $tables = [
        'admin_areas',
        'hiking_routes_ways',
        'hiking_routes',
        'places',
        'poles'
    ];


    static public function make(): self
    {
        return app(self::class);
    }


    /**
     * Undocumented function
     *
     * @param [type] $luaPath
     * @param [type] $pbfPath
     * @return void
     */
    public function import($luaPath, $pbfPath)
    {
        $this->createOsm2pgsqlDatabaseIfNoteExists();
        $this->osm2pgsql($luaPath, $pbfPath);
        $this->copyOsm2pgsqlTablesToTheLaravelDb();
    }

    protected function createOsm2pgsqlDatabaseIfNoteExists()
    {
        $dbName = $this->getDbName();
        try {
            DB::statement("CREATE DATABASE $dbName;");
        } catch (QueryException $e) {
            $message = $e->getMessage();
            if (strpos($message, 'already exists') === false) {
                throw $e;
            }
        }

        DB::connection(self::$slaveDbConnection)->statement('CREATE EXTENSION IF NOT EXISTS postgis;');
        DB::connection(self::$slaveDbConnection)->statement('CREATE EXTENSION IF NOT EXISTS hstore;');
    }

    protected function osm2pgsql($luaPath, $pbfPath)
    {
        $dbName = $this->getDbName();
        $dbUser = $this->getDbUser();
        $dbPassword = $this->getDbPassword();
        $dbHost = $this->getDbHost();

        $command = "PGPASSWORD=$dbPassword osm2pgsql -d $dbName -H '$dbHost' -U $dbUser -O flex -x -S $luaPath $pbfPath --slim";
        return $this->exec($command);
    }

    protected function exec($command)
    {
        $this->log("Executing command: $command");
        exec($command, $output, $return_var);

        // If the command failed, display an error message and return
        if ($return_var !== 0) {
            $outputString = implode(PHP_EOL, $output);
            $message = "Error executing \"$command\" command: $outputString";
            $this->log($message, 'error');
            throw new Exception($message);
        }

        return true;
    }

    protected function copyOsm2pgsqlTablesToTheLaravelDb()
    {
        $path = $this->dumpOsm2pgsqlTables();
        $this->importOsm2pgsqlTables($path);
    }

    protected function importOsm2pgsqlTables($path)
    {
        $this->exec("PGPASSWORD=\"{$this->getDbPassword()}\" psql -U \"{$this->getDbUser()}\" -h \"{$this->getDbHost()}\" \"{$this->getLaravelDbName()}\" < $path");

        $this->log('Imported osm2pgsql tables to the Laravel database');
        $check = unlink($path);
        if (!$check) {
            $this->log("Failed to delete $path", 'error');
        } else
            $this->log("Deleted $path");
    }


    protected function dumpOsm2pgsqlTables(): string
    {
        $this->log("Dumping osm2pgsql tables");
        $dumpRelativeDir = 'osm/pbf/';
        $path = storage_path($dumpRelativeDir . date('Y-m-d') . ".sql");


        \Spatie\DbDumper\Databases\PostgreSql::create()
            ->setDbName($this->getDbName())
            ->setUserName($this->getDbUser())
            ->setPassword($this->getDbPassword())
            ->setHost($this->getDbHost())
            ->addExtraOption('--clean')
            ->includeTables($this->getTablesToOverride())
            ->dumpToFile($path);

        return $path;
    }

    public function getTablesToOverride()
    {
        return $this::$tables;
    }


    protected function getLaravelDbName()
    {
        return config('database.connections.' . config('database.default') . '.database', 'osmfeatures');
    }

    protected function getDbName()
    {
        return config('database.connections.' . self::$slaveDbConnection . '.database');
    }

    protected function getDbHost()
    {
        return config('database.connections.' . self::$slaveDbConnection . '.host');;
    }
    protected function getDbUser()
    {
        return config('database.connections.' . self::$slaveDbConnection . '.username');
    }
    protected function getDbPassword()
    {
        return config('database.connections.' . self::$slaveDbConnection . '.password');
    }

    protected function log($message, $level = 'info')
    {
        if (app()->runningInConsole()) {
            Log::channel('stderr')->$level($message);
        }

        Log::$level($message);
    }
}
