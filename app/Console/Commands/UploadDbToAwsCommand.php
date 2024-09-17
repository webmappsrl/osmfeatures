<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class UploadDbToAwsCommand extends Command
{
    protected $signature = 'db:dump-to-aws';
    protected $description = 'Esegue un dump del database locale e lo carica su AWS S3';
    protected $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = Log::channel('aws');
    }

    public function handle()
    {
        $this->info('Inizio del dump del database...');
        $this->logger->info('Inizio del dump del database...');

        $appName = config('app.name');
        $filename = 'db_dump_' . config('app.env') . '_' . date('Y_m_d') . '.sql.gz';
        $path = storage_path($filename);
        $s3Path = "maphub/{$appName}/{$filename}";

        // Esegui il dump del database
        $this->dumpDatabase($path);

        // Carica il dump su S3
        $this->uploadToS3($path, $s3Path);

        // Elimina il dump locale
        unlink($path);

        // Elimina i vecchi dump da S3
        $this->deleteOldDumpsFromS3($appName);

        $this->info('Dump del database completato e caricato su AWS S3.');
    }

    private function dumpDatabase($path)
    {
        $this->info('Eseguo il dump del database...');
        $this->logger->info('Eseguo il dump del database...');
        $dbConfig = config('database.connections.pgsql');
        $command = sprintf(
            'PGPASSWORD=%s pg_dump --username=%s --host=%s %s | gzip > %s',
            $dbConfig['password'],
            $dbConfig['username'],
            $dbConfig['host'],
            $dbConfig['database'],
            $path
        );

        try {
            exec($command);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->info('Errore durante l\'esecuzione del dump del database.');
        }
    }

    private function uploadToS3($path, $s3Path)
    {
        $this->info('Carico il dump su AWS...');
        $this->logger->info('Carico il dump su AWS...');
        try {
            Storage::disk('wmdumps')->put($s3Path, fopen($path, 'r+')); // Usa il nuovo percorso S3
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->info('Errore durante il caricamento del dump su AWS S3.');
        }
    }

    private function deleteOldDumpsFromS3($appName)
    {
        $this->info('Controllo se esistono dump più vecchi di 7 giorni...');
        $this->logger->info('Controllo se esistono dump più vecchi di 7 giorni...');

        try {
            $s3Path = "maphub/{$appName}/";
            $files = Storage::disk('wmdumps')->files($s3Path);  // Recupera tutti i file nel percorso specificato
            $this->logger->info('File trovati su S3: ' . implode(', ', $files));

            $now = Carbon::now();

            foreach ($files as $file) {
                $this->logger->info('Controllando il file: ' . $file);

                $timestamp = Storage::disk('wmdumps')->lastModified($file);
                $fileDate = Carbon::createFromTimestamp($timestamp);
                $this->logger->info("Data del file: $fileDate");

                // Se il file ha più di 7 giorni, lo eliminiamo
                if ($fileDate->lt($now->subDays(7))) {
                    $this->info("Elimino il file $file che è più vecchio di 7 giorni.");
                    $this->logger->info("Elimino il file $file che è più vecchio di 7 giorni.");
                    Storage::disk('wmdumps')->delete($file);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore durante la cancellazione dei vecchi dump da S3: ' . $e->getMessage());
            $this->info('Errore durante la cancellazione dei vecchi dump da S3.');
        }
    }
}
