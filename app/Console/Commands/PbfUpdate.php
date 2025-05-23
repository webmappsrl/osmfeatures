<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Osm2pgsqlCrontabUpdate;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\text;

class PbfUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:update {pbf}';

    protected const pbfDir = 'osm/pbf/';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update database using the latest version of the chosen pbf file and looping over all the lua files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->createPbfFolderIfDoesntExist();


        $commandTime = now();

        [$pbfUrl, $pbfPath] = $this->getPbfUrlAndPath();

        // Download the PBF file
        $this->handleDownload($pbfUrl, $pbfPath);

        // Loop over all the lua files and perform the sync with osm2pgsql
        $luaFiles = glob(storage_path('osm/lua') . '/*.lua');
        // order the lua file
        $luaFiles = array_merge(
            array_filter($luaFiles, function ($luaFile) {
                return strpos($luaFile, 'poles.lua') !== false;
            }),
            array_filter($luaFiles, function ($luaFile) {
                return strpos($luaFile, 'admin_areas.lua') !== false;
            }),
            array_filter($luaFiles, function ($luaFile) {
                return strpos($luaFile, 'hiking_routes.lua') !== false;
            }),
            array_filter($luaFiles, function ($luaFile) {
                return strpos($luaFile, 'places.lua') !== false;
            })
        );
        foreach ($luaFiles as $luaFile) {
            //if lua file is pois.lua skip
            if (strpos($luaFile, 'pois.lua') !== false) {
                continue;
            }

            $this->osm2pgsqlSync($pbfPath, pathinfo($luaFile, PATHINFO_FILENAME));
        }

        //perform the hiking route updated_at update
        $this->info('Updating hiking routes updated_at field...');
        try {
            Artisan::call('osmfeatures:correct-hr-timestamps');
        } catch (\Exception $e) {
            $this->error('Error while updating hiking routes updated_at field: ' . $e->getMessage());
            Log::error('Error while updating hiking routes updated_at field: ' . $e->getMessage());
        }

        $commandTime = now()->diffInSeconds($commandTime) / 60;
        $this->info('osmfeatures:update command completed in ' . $commandTime . ' minutes.');
        Log::info('osmfeatures:update command completed in ' . $commandTime . ' minutes.');
    }

    protected function getPbfUrlAndPath()
    {
        $pbf = $this->argument('pbf');
        if (is_null($pbf)) {
            $pbf = text(
                label: 'Name of the PBF file to use',
                placeholder: 'original_italy_latest',
                hint: 'The file must be saved in storage/' . $this::pbfDir . ' with the specified name.',
                required: true,
                default: 'original_italy_latest',
            );

            switch ($pbf) {
                case 'europe':
                case 'europe-latest':
                    $pbfUrl = 'https://download.geofabrik.de/europe-latest.osm.pbf';
                    $pbfPath = storage_path($this::pbfDir . 'europe-latest.osm.pbf');
                    break;
                default:
                    $pbfUrl = 'https://download.geofabrik.de/europe/italy-latest.osm.pbf';
                    $pbfPath = storage_path($this::pbfDir . 'italy-latest.osm.pbf');
                    break;
            }
        } else {
            //TODO: handle region
            $pbfUrl = "https://download.geofabrik.de/europe/$pbf.pbf";
            if (! Http::head($pbfUrl)->successful()) {
                $pbfUrl = null;
            }
            $pbfPath = storage_path($this::pbfDir . $pbf . '.pbf');
        }


        return [$pbfUrl, $pbfPath];
    }

    protected function createPbfFolderIfDoesntExist()
    {
        // Create directory if it doesn't exist
        if (! file_exists(storage_path($this::pbfDir))) {
            mkdir(storage_path($this::pbfDir));
        }
    }

    /**
     * Handles the download of a PBF file from a specified URL.
     * If the file already exists, it will be overwritten.
     * @param string $pbfUrl The URL of the PBF file to download.
     * @param string $originalPath The path where the downloaded file should be saved.
     * @return bool Returns true if the download was successful, false otherwise.
     */
    protected function handleDownload($pbfUrl, $pbfPath)
    {
        if ($pbfUrl) {
            $this->info("Downloading PBF file from $pbfUrl...");
            try {
                $this->downloadPbf($pbfUrl, $pbfPath);
            } catch (Exception $e) {
                $this->error('Error during the PBF file download: ' . $e->getMessage());
                Log::error('Error during the PBF file download: ' . $e->getMessage());

                return false;
            }
        } else {
            $this->error('PBF file URL not specified.');

            return false;
        }

        return true;
    }

    /**
     * Downloads a PBF file from a specified URL.
     *
     * @param string $url The URL of the PBF file to download.
     * @param string $outputPath The path where the downloaded file should be saved.
     * @return bool Returns true if the download was successful, false otherwise.
     */
    protected function downloadPbf($url, $outputPath)
    {
        $downloadTime = now();
        try {
            $ch = curl_init($url);
            $fp = fopen($outputPath, 'w+');

            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // Set the callback for download progress
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function (
                $resource,
                $downloadSize,
                $downloaded,
                $uploadSize,
                $uploaded
            ) {
                // Show the amount of data downloaded / file size
                if ($downloadSize > 0) {
                    $this->output->write("\rDownloaded: " . $this->formatBytes($downloaded) . ' / ' . $this->formatBytes($downloadSize));
                }
            });

            $data = curl_exec($ch);

            $this->output->write("\n");

            curl_close($ch);
            fclose($fp);

            if (! $data) {
                echo 'cURL error: ' . curl_error($ch);
                $this->error('Error during the PBF file download.');

                return false;
            }

            $downloadTime = now()->diffInSeconds($downloadTime) / 60;
            $this->info("Download completed: $outputPath in $downloadTime minutes");
            Log::info("Download completed: $outputPath in $downloadTime minutes");

            return true;
        } catch (Exception $e) {
            $this->error('Error during the PBF file download: ' . $e->getMessage());
            Log::error('cURL error during the PBF file download: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Formats a size in bytes into a human-readable string.
     *
     * @param int $bytes The size in bytes to format.
     * @param int $precision The number of decimal places to include in the formatted string.
     * @return string Returns the formatted size string.
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Imports data from a PBF file into a PostgreSQL database using osm2pgsql.
     *
     * @param string $name The name of the import operation.
     * @param string $extractedPbfPath The path of the PBF file to import.
     * @return bool Returns true if the import was successful, false otherwise.
     */
    protected function osm2pgsqlSync($pbfPath, $luaFile)
    {
        $this->info("Importing data with osm2pgsql for $luaFile.lua...");
        $syncTime = now();

        $updateRecord = Osm2pgsqlCrontabUpdate::create([
            'imported_at' => now(),
            'from_lua' => $luaFile . '.lua',
            'from_pbf' => $pbfPath,
        ]);

        $dbName = env('DB_DATABASE', 'osmfeatures');
        $dbUser = env('DB_USERNAME', 'osmfeatures');
        $dbPassword = env('DB_PASSWORD', 'osmfeatures');
        $luaPath = 'storage/osm/lua/' . $luaFile . '.lua';
        if (! file_exists($luaPath)) {
            $this->error('Lua file not found at:' . $luaPath);

            Log::error('Lua file not found at:' . $luaPath);
            $updateRecord->update(['success' => false, 'log' => 'Lua file not found at:' . $luaPath]);

            return false;
        }
        $osm2pgsqlCmd = "PGPASSWORD=$dbPassword osm2pgsql -d $dbName -H 'db' -U $dbUser -O flex -x -S $luaPath $pbfPath --log-level=debug";
        $this->info('About to run osm2pgsql...');
        exec($osm2pgsqlCmd, $osm2pgsqlOutput, $osm2pgsqlReturnVar);

        if ($osm2pgsqlReturnVar != 0) {
            Log::error('Error during import with osm2pgsql.' . PHP_EOL . implode(PHP_EOL, $osm2pgsqlOutput));
            $this->error('Error during import with osm2pgsql.' . PHP_EOL . implode(PHP_EOL, $osm2pgsqlOutput));

            $updateRecord->update(['success' => false, 'log' => implode(PHP_EOL, $osm2pgsqlOutput)]);

            return false;
        }
        $syncTime = now()->diffInSeconds($syncTime) / 60;
        $this->info('Import successfully completed for ' . $luaFile . '.lua in ' . $syncTime . ' minutes');
        Log::info('Import successfully completed for ' . $luaFile . '.lua in ' . $syncTime . ' minutes');

        return true;
    }


    public function logToConsoleAndFile($message, $methodName = 'info')
    {
        $this->$methodName($message);
        Log::$methodName($message);
    }
}
