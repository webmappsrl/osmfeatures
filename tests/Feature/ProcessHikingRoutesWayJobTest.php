<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use App\Jobs\ProcessHikingRoutesWayJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProcessHikingRoutesWayJobTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('hiking_routes')) {
            Schema::create(
                'hiking_routes',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name')->nullable();
                    $table->bigInteger('osm_id')->nullable();
                    $table->string('osm_type')->nullable();
                    $table->dateTime('updated_at_osm')->nullable();
                    $table->dateTime('updated_at')->nullable();
                    $table->text('cai_scale')->nullable();
                    $table->integer('osm2cai_status')->nullable();
                    $table->integer('score')->nullable();
                    $table->text('osmc_symbol')->nullable();
                    $table->text('network')->nullable();
                    $table->text('survey_date')->nullable();
                    $table->text('roundtrip')->nullable();
                    $table->text('symbol')->nullable();
                    $table->text('symbol_it')->nullable();
                    $table->text('ascent')->nullable();
                    $table->text('descent')->nullable();
                    $table->text('distance')->nullable();
                    $table->text('duration_forward')->nullable();
                    $table->text('duration_backward')->nullable();
                    $table->text('from')->nullable();
                    $table->text('to')->nullable();
                    $table->text('rwn_name')->nullable();
                    $table->text('ref_REI')->nullable();
                    $table->text('maintenance')->nullable();
                    $table->text('maintenance_it')->nullable();
                    $table->text('operator')->nullable();
                    $table->text('state')->nullable();
                    $table->text('ref')->nullable();
                    $table->text('source')->nullable();
                    $table->text('source_ref')->nullable();
                    $table->text('note')->nullable();
                    $table->text('note_it')->nullable();
                    $table->text('old_ref')->nullable();
                    $table->text('note_project_page')->nullable();
                    $table->text('website')->nullable();
                    $table->text('wikimedia_commons')->nullable();
                    $table->text('description')->nullable();
                    $table->text('description_it')->nullable();
                    $table->jsonb('tags')->nullable();
                    $table->multiLineString('geom')->nullable();
                    $table->jsonb('members')->nullable();
                }
            );
            //create 200 hiking routes
            for ($i = 0; $i < 200; $i++) {
                $startLat = rand(3600, 4700) / 100; // Generate random latitude within bounds
                $startLon = rand(600, 1900) / 100; // Generate random longitude within bounds

                // Define a simple path by incrementing longitude and latitude
                $coords = [];
                for ($j = 0; $j < 5; $j++) {
                    $coords[] = sprintf('%.2f %.2f', $startLon + 0.01 * $j, $startLat + 0.01 * $j);
                }
                $lineString = implode(', ', $coords);

                $geomText = "MULTILINESTRING(($lineString))";

                DB::table('hiking_routes')->insert([
                    'name' => 'Hiking Route ' . $i,
                    'osm_id' => $i,
                    'osm_type' => 'R',
                    'geom' => DB::raw("ST_GeomFromText('$geomText', 4326)"),
                    'updated_at' => Carbon::today()->subDays(rand(70, 170)),
                    'updated_at_osm' => now(),
                    'cai_scale' => 'scale',
                    'osm2cai_status' => 1,
                    'score' => rand(1, 7),
                    'osmc_symbol' => 'symbol',
                    'network' => 'lwn',
                    'ref' => 'T8',
                    'source' => 'source',
                    'members' => json_encode([['ref' => $i, 'type' => 'w']]),
                    'tags' => json_encode(['wikidata' => 'value', 'wikipedia' => 'value', 'wikimedia_commons' => 'value']),
                    'survey_date' => '2021-01-01',
                    'roundtrip' => 'yes',
                    'symbol' => 'symbol',
                    'symbol_it' => 'symbol_it',
                    'ascent' => '1000',
                    'descent' => '1000',
                    'distance' => '10000',
                    'duration_forward' => '01:00:00',
                    'duration_backward' => '01:00:00',
                    'from' => 'start',
                    'to' => 'end',
                    'rwn_name' => 'rwn_name',
                    'ref_REI' => 'ref_REI',
                    'maintenance' => 'maintenance',
                    'maintenance_it' => 'maintenance_it',
                    'operator' => 'operator',
                    'state' => 'state',
                    'source_ref' => 'source_ref',
                    'note' => 'note',
                    'note_it' => 'note_it',
                    'old_ref' => 'old_ref',
                    'note_project_page' => 'note_project_page',
                    'website' => 'website',
                    'wikimedia_commons' => 'wikimedia_commons',
                    'description' => 'description',
                    'description_it' => 'description_it',
                ]);
            }
        }
        if (!Schema::hasTable('hiking_routes_ways')) {
            Schema::create(
                'hiking_routes_ways',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->bigInteger('osm_id')->nullable();
                    $table->string('osm_type')->nullable();
                    $table->dateTime('updated_at')->nullable();
                    $table->jsonb('tags')->nullable();
                }
            );
            //create 200 hiking routes ways
            for ($i = 0; $i < 200; $i++) {
                DB::table('hiking_routes_ways')->insert([
                    'osm_id' => $i,
                    'osm_type' => 'W',
                    'updated_at' => Carbon::today()->subDays(rand(1, 100)),
                    'tags' => json_encode(['wikidata' => 'value', 'wikipedia' => 'value', 'wikimedia_commons' => 'value']),
                ]);
            }
        }
    }

    public function test_artisan_command_correctly_dispatch_jobs()
    {
        Artisan::call('osmfeatures:correct-hr-timestamps');

        $this->assertDatabaseCount('hiking_routes_ways', 200);

        $this->assertDatabaseCount('hiking_routes', 200);

        $this->assertDatabaseCount('queue_monitor', 200);

        $this->assertDatabaseHas('queue_monitor', ['status' => 4, 'name' => 'App\Jobs\ProcessHikingRoutesWayJob']);
    }


    public function test_jobs_correctly_update_hiking_routes_when_updated_at_is_more_recent()
    {
        $hikingRoutesWay = DB::table('hiking_routes_ways')->get()->random();

        $hikingRoute = DB::table('hiking_routes')->whereJsonContains('members', [['type' => 'w', 'ref' =>
        $hikingRoutesWay->osm_id]])->first();

        $hikingRoutesWay->updated_at = now();

        $job = new ProcessHikingRoutesWayJob($hikingRoutesWay);

        $job->handle();

        $updatedHikingRoute = DB::table('hiking_routes')->where('id', $hikingRoute->id)->first();

        $this->assertGreaterThan($hikingRoute->updated_at, $updatedHikingRoute->updated_at);
    }

    public function test_jobs_does_not_update_hiking_routes_when_updated_at_is_not_more_recent()
    {
        $hikingRoutesWay = DB::table('hiking_routes_ways')->get()->random();

        $hikingRoute = DB::table('hiking_routes')->whereJsonContains('members', [['type' => 'w', 'ref' =>
        $hikingRoutesWay->osm_id]])->first();

        $hikingRoutesWay->updated_at = $hikingRoute->updated_at;

        $job = new ProcessHikingRoutesWayJob($hikingRoutesWay);

        $job->handle();

        $updatedHikingRoute = DB::table('hiking_routes')->where('id', $hikingRoute->id)->first();

        $this->assertEquals($hikingRoute->updated_at, $updatedHikingRoute->updated_at);
    }
}
