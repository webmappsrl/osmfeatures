<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Place;

class FindOsmfeaturesFromFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osmfeatures:find-enrichables-from-file {filepath : The path to the input file} {model=Place : The name of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new .txt file containing a filtered list of osmfeatures enrichables (with wikipedia and wikidata tags) starting from the provided file ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inputFile = $this->argument('filepath');
        $model = $this->argument('model');
        $model = "App\\Models\\$model";
        $outputFile = base_path("$model-enrichables.txt");

        if (!file_exists($inputFile)) {
            $this->error("The file at path $inputFile does not exist.");
            return 1;
        }

        $inputFileHandle = fopen($inputFile, 'r');
        if (!$inputFileHandle) {
            $this->error("The file at path $inputFile could not be opened.");
            return 1;
        }

        $outputFileHandle = fopen($outputFile, 'w'); // 'w' mode will clear the file content
        if (!$outputFileHandle) {
            fclose($inputFileHandle);
            $this->error("The file at path $outputFile could not be opened.");
            return 1;
        }

        $count = 0;

        while (($line = fgets($inputFileHandle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $osmType = substr($line, 0, 1);
            $osmId = substr($line, 1);
            $osmfeature = $model::where('osm_type', $osmType)->where('osm_id', $osmId)->first();
            if ($osmfeature) {
                $tags = json_decode($osmfeature->tags, true);
                $wikipedia = $tags['wikipedia'] ?? null;
                $wikidata = $tags['wikidata'] ?? null;

                $hasEnrichments = $osmfeature->enrichment && !empty($osmfeature->enrichment->data);

                if (($wikipedia || $wikidata) && !$hasEnrichments) {
                    $count++;
                    $outputLine = "$osmType$osmId\n";
                    fwrite($outputFileHandle, $outputLine);
                }
            }
        }

        fclose($inputFileHandle);
        fclose($outputFileHandle);

        $this->info("The file at path $outputFile has been created with $count entries.");
        return 0;
    }
}
