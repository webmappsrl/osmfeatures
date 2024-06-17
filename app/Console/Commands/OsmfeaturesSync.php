<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class OsmfeaturesSync extends Command
{

    public function __construct()
    {
        parent::__construct();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $this->logger = Log::channel('osmfeatures');
    }
    protected $signature = 'osmfeatures:sync {defaultName?} {defaultLua?} {--skip-download} {defaultPbf?}';

    protected $description = 'Synchronize OpenStreetMap data by downloading a PBF file, use osmium to extract a specific area based on bounding box, and save the result.';

    public function handle()
    {
        Log::channel('osmfeatures')->info('Starting OSM features synchronization...');

        $skipDownload = confirm(
            label: 'Skip download and use a local PBF file?',
            default: $this->option('skip-download'),
            hint: 'If you already have the PBF file, you can skip the download.'
        );
        Log::channel('osmfeatures')->info('User selected skip download: ' . ($skipDownload ? 'Yes' : 'No'));

        if (!$skipDownload) {
            $pbfUrl = text(
                label: 'URL of the PBF file to download',
                placeholder: 'https://download.geofabrik.de/europe/italy-latest.osm.pbf',
                hint: 'If you want to skip the download, leave this field empty and use the --skip-download option.',
                required: true,
                default: $this->argument('defaultPbf') ?? 'https://download.geofabrik.de/europe/italy/centro-latest.osm.pbf',
            );
            Log::info('PBF URL: ' . $pbfUrl);

            $name = text(
                label: 'Name of the PBF file to save',
                placeholder: 'italy_latest',
                hint: 'The final file will be saved in storage/app/osm/pbf/ with the specified name.',
                required: true,
                default: $this->argument('defaultName') ?? 'italy_centro_latest',
            );
            Log::info('PBF file name to save: ' . $name);
        } else {
            $name = text(
                label: 'Name of the PBF file to use',
                placeholder: 'italy-latest.osm.pbf',
                hint: 'The file must be saved in storage/osm/pbf/ with the specified name.',
                required: true,
                default: $this->argument('defaultName') ?? 'italy_centro_latest',
            );
            Log::channel('osmfeatures')->info('PBF file name to use: ' . $name);
        }

        $luaFile = text(
            label: 'Lua file to use for osm2pgsql',
            placeholder: 'pois',
            hint: 'The Lua file must be saved in storage/osm/lua/ with the specified name (eg. pois.lua).',
            required: true,
            default: $this->argument('defaultLua') ?? 'pois',
        );
        Log::channel('osmfeatures')->info('Lua file: ' . $luaFile);

        $this->info("Starting synchronization for $name...");
        Log::channel('osmfeatures')->info("Starting synchronization for $name...");

        if (!file_exists(storage_path('osm/pbf'))) {
            mkdir(storage_path('osm/pbf'), 0o755, true);
            Log::channel('osmfeatures')->info('Directory created: ' . storage_path('osm/pbf'));
        }

        $originalPath = storage_path("osm/pbf/original_$name.pbf");

        if (!file_exists($originalPath) && $skipDownload) {
            $this->error('PBF file not found at: ' . $originalPath . ' Please make sure the file exists.');
            Log::channel('osmfeatures')->error('PBF file not found at: ' . $originalPath);

            return false;
        }

        if (!$skipDownload) {
            if (!$this->handleDownload($pbfUrl, $originalPath)) {
                Log::channel('osmfeatures')->error('Failed to download PBF file from ' . $pbfUrl);
                return false;
            }
        }

        if (!$this->osm2pgsqlSync($name, $originalPath, $luaFile)) {
            Log::channel('osmfeatures')->error('Failed to synchronize with osm2pgsql.');
            return false;
        }

        $this->info("Synchronization completed for $name.");
        Log::channel('osmfeatures')->info("Synchronization completed for $name.");

        return true;
    }

    public function handleDownload($pbfUrl, $originalPath)
    {
        if ($pbfUrl && Http::get($pbfUrl)->successful()) {
            $this->info("Downloading PBF file from $pbfUrl...");
            Log::channel('osmfeatures')->info("Downloading PBF file from $pbfUrl to $originalPath");

            if (!$this->downloadPbf($pbfUrl, $originalPath)) {
                Log::channel('osmfeatures')->error('Download failed from ' . $pbfUrl);
                return false;
            }
        } else {
            $this->error('PBF file URL not valid.');
            Log::channel('osmfeatures')->error('PBF file URL not valid.');

            return false;
        }

        return true;
    }

    public function osm2pgsqlSync($name, $pbfPath, $luaFile)
    {
        $this->info("Importing data with osm2pgsql for $name...");
        Log::channel('osmfeatures')->info("Importing data with osm2pgsql for $name from $pbfPath using $luaFile.lua");

        $dbName = env('DB_DATABASE', 'osmfeatures');
        $dbUser = env('DB_USERNAME', 'osmfeatures');
        $dbPassword = env('DB_PASSWORD', 'osmfeatures');
        $luaPath = storage_path('osm/lua/' . $luaFile . '.lua');

        if (!file_exists($luaPath)) {
            $this->error('Lua file not found at: ' . $luaPath);
            Log::channel('osmfeatures')->error('Lua file not found at: ' . $luaPath);

            return false;
        }

        $osm2pgsqlCmd = "PGPASSWORD=$dbPassword osm2pgsql -d $dbName -H 'db' -U $dbUser -O flex -x -S $luaPath $pbfPath --slim --log-level=debug";
        $this->info('About to run osm2pgsql...');
        Log::channel('osmfeatures')->info('Running osm2pgsql with command: ' . $osm2pgsqlCmd);

        exec($osm2pgsqlCmd, $osm2pgsqlOutput, $osm2pgsqlReturnVar);

        if ($osm2pgsqlReturnVar != 0) {
            $this->error('Error during import with osm2pgsql.');
            Log::channel('osmfeatures')->error('osm2pgsql import failed with return code: ' . $osm2pgsqlReturnVar);

            return false;
        }

        $this->info('Import successfully completed.');
        Log::channel('osmfeatures')->info('Import successfully completed.');

        return true;
    }

    public function downloadPbf($url, $outputPath)
    {
        try {
            $ch = curl_init($url);
            $fp = fopen($outputPath, 'w+');
            curl_setopt($ch, CURLOPT_TIMEOUT, 500);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) {
                if ($downloadSize > 0) {
                    $progress = 'Downloaded: ' . $this->formatBytes($downloaded) . ' / ' . $this->formatBytes($downloadSize);
                    $this->output->write("\r" . $progress);
                    Log::info($progress);
                }
            });

            $data = curl_exec($ch);

            $this->output->write("\n");
            curl_close($ch);
            fclose($fp);

            if (!$data) {
                $error = 'cURL error: ' . curl_error($ch);
                $this->error($error);
                Log::channel('osmfeatures')->error($error);

                return false;
            }

            $this->info("Download completed: $outputPath");
            Log::channel('osmfeatures')->info("Download completed: $outputPath");

            return true;
        } catch (Exception $e) {
            $error = 'Error during the PBF file download: ' . $e->getMessage();
            $this->error($error);
            Log::channel('osmfeatures')->error($error);

            return false;
        }
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}