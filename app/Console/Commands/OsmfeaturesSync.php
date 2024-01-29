<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class OsmfeaturesSync extends Command
{
    protected $signature = 'osmfeatures:sync';

    protected $description = 'Synchronize OpenStreetMap data by downloading a PBF file, use osmium to extract a specific area based on bounding box, and save the result.';

    public function handle()
    {
        $isCiEnvironment = env('CI_ENVIRONMENT') === 'true';
        if (! $isCiEnvironment) {
            $name = text(
                label: 'Name of the final file after extraction with osmium',
                placeholder: 'Montepisano_pois',
                hint: 'The final file will be saved in storage/app/osm/pbf/ with the specified name.',
                required: true,
                default: 'Montepisano_pois',
            );
            $dbHost = text(
                label: 'PostgreSQL database host',
                placeholder: 'localhost',
                hint: 'To find the database host for a docker container, run: docker inspect -f \'{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}\' <container_name>',
                required: true,
                default: '172.30.0.3',
            );
            $luaFile = text(
                label: 'Lua file to use for osm2pgsql',
                placeholder: 'pois',
                hint: 'The Lua file must be saved in storage/osm/lua/ with the specified name (eg. pois.lua).',
                required: true,
                default: 'pois',
            );
            $pbfUrl = text(
                label: 'URL of the PBF file to download',
                placeholder: 'https://download.geofabrik.de/europe/italy-latest.osm.pbf',
                hint: 'If you want to skip the download, leave this field empty and use the --skip-download option.',
                required: false,
                default: 'https://download.geofabrik.de/europe/italy/centro-latest.osm.pbf',

            );
            $bbox = text(
                label: 'Bounding box for data extraction',
                placeholder: '10.2,43.5,10.3,43.6',
                hint: 'If you want to skip the download, leave this field empty and use the --skip-download option.',
                required: false
            );
            $skipDownload = confirm(
                label: 'Skip download?',
                default: false,
                hint: 'If you already have the PBF file, you can skip the download and use the --skip-download option.'
            );
        } else {
            $name = env('DEFAULT_NAME');
            $dbHost = env('DEFAULT_HOST');
            $luaFile = env('DEFAULT_LUA');
            $pbfUrl = env('DEFAULT_PBF');
        }

        $this->info("Starting synchronization for $name...");

        // Create directory if it doesn't exist
        if (! file_exists(storage_path('osm/pbf'))) {
            mkdir(storage_path('osm/pbf'));
        }

        // Define paths
        $originalPath = storage_path("osm/pbf/original_$name.pbf");
        $extractedPbfPath = storage_path("osm/pbf/$name.pbf");

        // Handle download
        if (! $skipDownload) {
            $this->handleDownload($pbfUrl, $originalPath);
        }

        // Handle extraction
        if ($bbox) {
            $this->osmiumExtraction($bbox, $originalPath, $extractedPbfPath);
        } else {
            // If no bbox is specified, use the original PBF file for import
            $extractedPbfPath = $originalPath;
        }

        // Sync with osm2pgsql
        $this->osm2pgsqlSync($name, $extractedPbfPath, $dbHost, $luaFile);
    }

    /**
     * Handles the download of a PBF file from a specified URL.
     * If the file already exists, it will be overwritten.
     * @param string $pbfUrl The URL of the PBF file to download.
     * @param string $originalPath The path where the downloaded file should be saved.
     * @return bool Returns true if the download was successful, false otherwise.
     */
    protected function handleDownload($pbfUrl, $originalPath)
    {
        if ($pbfUrl) {
            $this->info("Downloading PBF file from $pbfUrl...");
            if (! $this->downloadPbf($pbfUrl, $originalPath)) {
                return false;
            }
        } else {
            $this->error('PBF file URL not specified.');

            return false;
        }

        return true;
    }

    /**
     * Extracts a specific area of interest from a PBF file using osmium.
     *
     * @param string $bbox The bounding box of the area to extract.
     * @param string $originalPath The path of the original PBF file.
     * @param string $extractedPbfPath The path where the extracted file should be saved.
     * @return bool Returns true if the extraction was successful, false otherwise.
     */
    protected function osmiumExtraction($bbox, $originalPath, $extractedPbfPath)
    {
        if ($bbox && file_exists($originalPath)) {
            $this->info("Extracting area of interest [ $bbox ] from $originalPath...");
            $osmiumCmd = "osmium extract -b $bbox $originalPath -o $extractedPbfPath";
            exec($osmiumCmd, $osmiumOutput, $osmiumReturnVar);

            if ($osmiumReturnVar != 0) {
                $this->error('Error during extraction with osmium.');

                return false;
            }

            $this->info("Extraction completed: $extractedPbfPath");
        } else {
            $this->error('PBF file not found or bbox not specified.');

            return false;
        }

        return true;
    }

    /**
     * Imports data from a PBF file into a PostgreSQL database using osm2pgsql.
     *
     * @param string $name The name of the import operation.
     * @param string $extractedPbfPath The path of the PBF file to import.
     * @param string $dbHost The host of the PostgreSQL database.
     * @return bool Returns true if the import was successful, false otherwise.
     */
    protected function osm2pgsqlSync($name, $extractedPbfPath, $dbHost, $luaFile)
    {
        $this->info("Importing data with osm2pgsql for $name...");

        $dbName = env('DB_DATABASE', 'osmfeatures');
        $dbUser = env('DB_USERNAME', 'osmfeatures');
        $dbPassword = env('DB_PASSWORD', 'osmfeatures');
        $luaPath = 'storage/osm/lua/'.$luaFile.'.lua';
        if (! file_exists($luaPath)) {
            $this->error('Lua file not found at:'.$luaPath);

            return false;
        }
        $osm2pgsqlCmd = "PGPASSWORD=$dbPassword osm2pgsql -d $dbName -H $dbHost -U $dbUser -O flex -S $luaPath $extractedPbfPath";
        $this->info('About to run osm2pgsql...');
        exec($osm2pgsqlCmd, $osm2pgsqlOutput, $osm2pgsqlReturnVar);

        if ($osm2pgsqlReturnVar != 0) {
            $this->error('Error during import with osm2pgsql.');

            return false;
        }

        $this->info('Import successfully completed.');

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
        try {
            $ch = curl_init($url);
            $fp = fopen($outputPath, 'w+');

            curl_setopt($ch, CURLOPT_TIMEOUT, 500);
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
                    $this->output->write("\rDownloaded: ".$this->formatBytes($downloaded).' / '.$this->formatBytes($downloadSize));
                }
            });

            $data = curl_exec($ch);

            // Go to the line after the download is complete
            $this->output->write("\n");

            curl_close($ch);
            fclose($fp);

            if (! $data) {
                echo 'cURL error: '.curl_error($ch);
                $this->error('Error during the PBF file download.');

                return false;
            }

            $this->info("Download completed: $outputPath");

            return true;
        } catch (Exception $e) {
            $this->error('Error during the PBF file download: '.$e->getMessage());
            Log::error('cURL error during the PBF file download: '.$e->getMessage());

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

        return round($bytes, $precision).' '.$units[$pow];
    }
}
