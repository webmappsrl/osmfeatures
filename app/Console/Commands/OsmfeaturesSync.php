<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OsmfeaturesSync extends Command
{
    protected $signature = 'osmfeatures:sync 
                            {name : Il nome del file finale dopo l\'estrazione con osmium. Obbligatorio}
                            {pbf? : URL del file PBF da scaricare. Non richiesto se si usa l\'opzione --skip-download}
                            {bbox? : Bounding box per l\'estrazione dei dati (formato: minLon,minLat,maxLon,maxLat). Non richiesto se si usa l\'opzione --skip-download}
                            {--skip-download : Salta il download del file e usa un file PBF esistente nella cartella storage/app/osm/ riconoscibile dal nome specificato.}';

    protected $description = 'Sincronizza dati OpenStreetMap scaricando un file PBF, utilizza osmium per estrarre una specifica area basata su bounding box, e salva il risultato.';

    public function handle()
    {
        $name = $this->argument('name');
        $pbfUrl = $this->argument('pbf');
        $bbox = $this->argument('bbox');
        $skipDownload = $this->option('skip-download');

        $this->info("Inizio sincronizzazione per $name...");

        if (! file_exists(storage_path('app/osm'))) {
            mkdir(storage_path('app/osm'));
        }

        $originalPath = storage_path("osm/pbf/original_$name.pbf");
        $extractedPbfPath = storage_path("osm/pbf/$name.pbf");

        if (! $skipDownload) {
            $this->handleDownload($pbfUrl, $originalPath);
        }

        if (! file_exists($extractedPbfPath) && $bbox) {
            $this->osmiumExtraction($bbox, $originalPath, $extractedPbfPath);
        } else {
            //se non è stato specificato un bbox, utilizza il file PBF originale per l'importazione
            $extractedPbfPath = $originalPath;
        }

        $this->osm2pgsqlSync($name, $extractedPbfPath);
    }

    protected function handleDownload($pbfUrl, $originalPath)
    {
        if ($pbfUrl) {
            $this->info("Scaricando il file PBF da $pbfUrl...");
            if (! $this->downloadPbf($pbfUrl, $originalPath)) {
                return false;
            }
        } else {
            $this->error('URL del file PBF non fornito.');

            return false;
        }

        return true;
    }

    protected function osmiumExtraction($bbox, $originalPath, $extractedPbfPath)
    {
        if ($bbox && file_exists($originalPath)) {
            $this->info("Estrazione dell'area di interesse [ $bbox ] da$originalPath...");
            $osmiumCmd = "osmium extract -b $bbox $originalPath -o $extractedPbfPath";
            exec($osmiumCmd, $osmiumOutput, $osmiumReturnVar);

            if ($osmiumReturnVar != 0) {
                $this->error("Errore durante l'estrazione con osmium.");

                return false;
            }

            $this->info("Estrazione completata: $extractedPbfPath");
        } else {
            $this->error('File PBF non trovato o bbox non specificato.');

            return false;
        }

        return true;
    }

    protected function osm2pgsqlSync($name, $extractedPbfPath)
    {
        $this->info("Importazione dei dati in corso con osm2pgsql per $name...");

        $dbName = 'osmfeatures';
        $dbHost = '172.31.0.2';
        $dbUser = 'osmfeatures';
        $luaPath = 'storage/osm/lua/pois.lua';
        $osm2pgsqlCmd = "osm2pgsql -d $dbName -H $dbHost -U $dbUser -W -O flex -S $luaPath $extractedPbfPath";

        $this->info('Stai per eseguire osm2pgsql. Inserisci la password del database.');
        exec($osm2pgsqlCmd, $osm2pgsqlOutput, $osm2pgsqlReturnVar);

        if ($osm2pgsqlReturnVar != 0) {
            $this->error("Errore durante l'importazione con osm2pgsql.");

            return false;
        }

        $this->info('Importazione completata con successo.');

        return true;
    }

    /**
     * Scarica un file PBF da un URL specificato.
     *
     * @param string $url URL del file PBF da scaricare
     * @param string $outputPath Percorso in cui salvare il file scaricato
     */
    protected function downloadPbf($url, $outputPath)
    {
        try {
            $ch = curl_init($url);
            $fp = fopen($outputPath, 'w+');

            curl_setopt($ch, CURLOPT_TIMEOUT, 500);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // Imposta la callback per il progresso del download
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function (
                $resource,
                $downloadSize,
                $downloaded,
                $uploadSize,
                $uploaded
            ) {
                // Mostra la quantità di dati scaricati / dimensione del file
                if ($downloadSize > 0) {
                    $this->output->write("\rScaricati: ".$this->formatBytes($downloaded).' / '.$this->formatBytes($downloadSize));
                }
            });

            $data = curl_exec($ch);

            // Va a capo dopo il completamento del download
            $this->output->write("\n");

            curl_close($ch);
            fclose($fp);

            if (! $data) {
                echo 'cURL error: '.curl_error($ch);
                $this->error('Errore durante il download del file PBF.');

                return false;
            }

            $this->info("Download completato: $outputPath");

            return true;
        } catch (Exception $e) {
            $this->error('Errore durante il download del file PBF: '.$e->getMessage());
            Log::error('Errore di cURL durante il download del file PBF: '.$e->getMessage());

            return false;
        }
    }

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
