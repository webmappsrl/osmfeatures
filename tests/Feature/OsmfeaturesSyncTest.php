<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Tester\CommandTester;
use App\Console\Commands\OsmfeaturesSync;

class OsmfeaturesSyncTest extends TestCase
{
    public function test_command_provide_expected_questions(): void
    {
        //cant test this in CI
        if (env('APP_ENV') === 'testing') {
            return;
        }

        $this->artisan('osmfeatures:sync')
            ->expectsQuestion('Skip download and use a local PBF file?', 'yes')
            ->expectsQuestion('Name of the PBF file to use', 'italy_centro_latest')
            ->expectsQuestion('Lua file to use for osm2pgsql', 'not_existing_lua'); // this lua file does not exist so the command should not run.
    }

    public function test_download_pbf(): void
    {

        $pbfUrl = 'https://download.geofabrik.de/europe/andorra-latest.osm.pbf';
        $pbfPath = storage_path('tests/original_andorra_latest.pbf');
        $command = new OsmfeaturesSync();
        $command->setLaravel($this->app);

        $this->assertSame(true, $command->downloadPbf($pbfUrl, $pbfPath));
        $this->assertTrue(File::exists($pbfPath));
    }

    public function test_format_bytes(): void
    {
        $bytes = 1024;
        $precision = 2;

        $command = new OsmfeaturesSync();
        $command->setLaravel($this->app);

        $this->assertSame('1 KB', $command->formatBytes($bytes, $precision));
    }

    public function test_command_handle_download_return_false_if_parameters_are_not_correct(): void
    {
        $pbfUrl = 'https://download.geofabrik.de/not_existing_file.pbf';
        $pbfPath = storage_path('tests/not_existing_file.pbf');
        $command = new OsmfeaturesSync();
        $command->setLaravel($this->app);

        $this->assertSame(false, $command->handleDownload($pbfUrl, $pbfPath));
    }

    public function test_command_osm2pgsqlSync_return_false_if_lua_file_is_not_valid(): void
    {
        $pbfPath = storage_path('tests/original_italy_latest.pbf');
        $luaFile = 'not_existing_lua';
        $command = new OsmfeaturesSync();
        $command->setLaravel($this->app);

        $this->assertSame(false, $command->osm2pgsqlSync('italy_latest', $pbfPath, $luaFile));
    }

    public function test_command_osm2pgsqlSync_return_false_if_osm2pgsql_script_is_returning_error(): void
    {
        $pbfPath = storage_path('tests/not_existing.pbf');
        $luaFile = 'not_existing_lua';
        $command = new OsmfeaturesSync();
        $command->setLaravel($this->app);

        $this->assertSame(false, $command->osm2pgsqlSync('italy_latest', $pbfPath, $luaFile));
    }

    public function test_command_osm2pgsqlSync_return_correct_outputs_if_osm2pgsql_script_is_returning_error(): void
    {
        $pbfPath = storage_path('osm/pbf/original_not_existing.pbf');
        $luaFile = 'not_existing_lua';
        $command = new OsmfeaturesSync();
        $command->setLaravel($this->app);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'defaultLua' => $luaFile,
            '--skip-download' => true,
            'defaultPbf' => $pbfPath,
            'defaultName' => 'not_existing',
        ]);

        $this->assertStringContainsString('PBF file not found at: ' . $pbfPath, $commandTester->getDisplay());
    }
}
