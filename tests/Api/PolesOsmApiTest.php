<?php

namespace Tests\Api;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PolesOsmApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // 1	osm_type	bpchar(1)	NO	NULL	NULL		NULL
        // 2	osm_id	int8	NO	NULL	NULL		NULL
        // 3	id	int4	NO	NULL	"nextval('poles_tmp_id_seq'::regclass)"		NULL
        // 4	updated_at	text	YES	NULL	NULL		NULL
        // 5	name	text	YES	NULL	NULL		NULL
        // 6	tags	jsonb	YES	NULL	NULL		NULL
        // 7	geom	geometry(Point,3857)	YES	NULL	NULL		NULL
        // 8	ref	text	YES	NULL	NULL		NULL
        // 9	ele	text	YES	NULL	NULL		NULL
        // 10	destination	text	YES	NULL	NULL		NULL
        // 11	support	text	YES	NULL	NULL		NULL

        if (!Schema::hasTable('poles')) {
            Schema::create('poles', function (Blueprint $table) {
                $table->string('osm_type');
                $table->bigInteger('osm_id');
                $table->increments('id');
                $table->timestamp('updated_at');
                $table->string('name');
                $table->jsonb('tags')->nullable();
                $table->point('geom');
                $table->string('ref')->nullable();
                $table->string('ele')->nullable();
                $table->string('destination')->nullable();
                $table->string('support')->nullable();
                $table->integer('score')->nullable();
            });

            //create 200 poles
            for ($i = 0; $i < 200; $i++) {
                // generate random point inside Italy bounding box
                $lat = rand(3600, 4700) / 100;
                $lon = rand(600, 1900) / 100;

                DB::table('poles')->insert([
                    'osm_type' => 'N',
                    'osm_id' => $i,
                    'updated_at' => now(),
                    'name' => 'Pole ' . $i,
                    'tags' => json_encode(['tag' => 'value']),
                    'geom' => DB::raw("ST_GeomFromText('POINT($lon $lat)')"),
                    'ref' => 'ref',
                    'ele' => '1000',
                    'destination' => 'destination',
                    'support' => 'support',
                    'score' => rand(1, 5),
                ]);
            }
            $this->usingTestData = true;
        }
    }

    /**
     * Test if the endpoint returns a 200 status code with the correct parameters
     * @test
     */
    public function list_poles_api_returns_code_200()
    {
        $pole = DB::table('poles')->inRandomOrder()->first();
        $osmType = match ($pole->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/poles/osm/' . $osmType . '/' . $pole->osm_id);

        $response->assertStatus(200);
    }

    /**
     * Test if the endpoint returns a 404 status code with wrong osm type
     * @test
     */
    public function list_poles_api_returns_code_404_with_wrong_osm_type()
    {
        $pole = DB::table('poles')->inRandomOrder()->first();
        $response = $this->get('/api/v1/features/poles/osm/randomvalue/' . $pole->osm_id);

        $response->assertStatus(404);
    }

    /**
     * Test if the endpoint returns a response with the correct structure
     * @test
     */
    public function list_poles_api_returns_correct_structure()
    {
        $pole = DB::table('poles')->inRandomOrder()->first();
        $osmType = match ($pole->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/poles/osm/' . $osmType . '/' . $pole->osm_id);

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('type')
                    ->has('properties')
                    ->has('geometry')
                    ->has('properties.osm_type')
                    ->has('properties.osm_id')
                    ->has('properties.id')
                    ->has('properties.updated_at')
                    ->has('properties.name')
                    ->has('properties.ref')
                    ->has('properties.ele')
                    ->has('properties.destination')
                    ->has('properties.support')
                    ->has('properties.score')
                    ->has('properties.osm_url')
                    ->has('properties.osm_api')
                    ->has('properties.osm_tags')
                    ->has('properties.wikidata')
                    ->has('properties.wikipedia')
                    ->has('properties.wikimedia_commons');
            }
        );
    }
}