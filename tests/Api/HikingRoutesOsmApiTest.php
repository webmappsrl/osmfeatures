<?php

namespace Tests\Api;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HikingRoutesOsmApiTest extends TestCase
{
    use RefreshDatabase;

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
                    'updated_at' => now(),
                    'updated_at_osm' => now(),
                    'cai_scale' => 'scale',
                    'osm2cai_status' => 1,
                    'score' => rand(1, 7),
                    'osmc_symbol' => 'symbol',
                    'network' => 'lwn',
                    'ref' => 'T8',
                    'source' => 'source',
                    'members' => json_encode(['member' => 'value']),
                    'tags' => json_encode(['tag' => 'value']),
                ]);
            }
            $this->usingTestData = true;
        }
    }

    /**
     * Test if the endpoint returns code 200 with correct parameters
     * @test
     */
    public function osm_hiking_routes_api_returns_code_200()
    {
        $hikingRoute = DB::table('hiking_routes')->inRandomOrder()->first();

        $osmType = match ($hikingRoute->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/hiking-routes/osm/' . $osmType . '/' . $hikingRoute->osm_id);

        $response->assertStatus(200);
    }

    /**
     * Test if the endpoint returns code 404 with wrong osm type
     * @test
     */
    public function osm_hiking_routes_api_returns_code_404_with_wrong_osm_type()
    {
        $hikingRoute = DB::table('hiking_routes')->inRandomOrder()->first();

        $response = $this->get('/api/v1/features/hiking-routes/osm/randomvalue/' . $hikingRoute->osm_id);

        $response->assertStatus(404);
    }

    /**
     * Test if the endpoint returns a response with the correct structure
     * @test
     */
    public function osm_hiking_routes_api_returns_correct_structure()
    {
        $hikingRoute = DB::table('hiking_routes')->inRandomOrder()->first();

        $osmType = match ($hikingRoute->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/hiking-routes/osm/' . $osmType . '/' . $hikingRoute->osm_id);

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('properties')
                    ->has('geometry')
                    ->has('type')
                    ->has('properties.osm_type')
                    ->has('properties.osm_id')
                    ->has('properties.id')
                    ->has('properties.updated_at_osm')
                    ->has('properties.updated_at')
                    ->has('properties.name')
                    ->has('properties.cai_scale')
                    ->has('properties.osm2cai_status')
                    ->has('properties.score')
                    ->has('properties.osmc_symbol')
                    ->has('properties.network')
                    ->has('properties.survey_date')
                    ->has('properties.roundtrip')
                    ->has('properties.symbol')
                    ->has('properties.symbol_it')
                    ->has('properties.ascent')
                    ->has('properties.descent')
                    ->has('properties.distance')
                    ->has('properties.duration_forward')
                    ->has('properties.duration_backward')
                    ->has('properties.from')
                    ->has('properties.to')
                    ->has('properties.rwn_name')
                    ->has('properties.ref_REI')
                    ->has('properties.maintenance')
                    ->has('properties.maintenance_it')
                    ->has('properties.operator')
                    ->has('properties.state')
                    ->has('properties.ref')
                    ->has('properties.source')
                    ->has('properties.source_ref')
                    ->has('properties.note')
                    ->has('properties.note_it')
                    ->has('properties.old_ref')
                    ->has('properties.note_project_page')
                    ->has('properties.website')
                    ->has('properties.wikimedia_commons')
                    ->has('properties.description')
                    ->has('properties.description_it')
                    ->has('properties.osm_tags')
                    ->has('properties.members')
                    ->has('properties.wikidata')
                    ->has('properties.wikipedia');
            }
        );
    }
}