<?php

namespace Tests\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PlacesOsmApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        //       1	osm_type	bpchar(1)	NO	NULL	NULL		NULL
        // 2	osm_id	int8	NO	NULL	NULL		NULL
        // 3	id	int4	NO	NULL	"nextval('places_tmp_id_seq'::regclass)"		NULL
        // 4	updated_at	text	YES	NULL	NULL		NULL
        // 5	name	text	YES	NULL	NULL		NULL
        // 6	class	text	NO	NULL	NULL		NULL
        // 7	subclass	text	YES	NULL	NULL		NULL
        // 8	geom	geometry(Point,3857)	NO	NULL	NULL		NULL
        // 9	tags	jsonb	YES	NULL	NULL		NULL
        // 10	elevation	int4	YES	NULL	NULL		NULL

        if (! Schema::hasTable('places')) {
            Schema::create(
                'places',
                function (Blueprint $table) {
                    $table->string('osm_type');
                    $table->bigInteger('osm_id');
                    $table->increments('id');
                    $table->timestamp('updated_at');
                    $table->string('name');
                    $table->string('class');
                    $table->string('subclass')->nullable();
                    $table->point('geom');
                    $table->jsonb('tags')->nullable();
                    $table->integer('elevation')->nullable();
                    $table->integer('score')->nullable();
                }
            );

            //create 200 places
            for ($i = 0; $i < 200; $i++) {
                // generate random point inside Italy bounding box
                $lat = rand(3600, 4700) / 100;
                $lon = rand(600, 1900) / 100;

                DB::table('places')->insert([
                    'osm_type' => 'N',
                    'osm_id' => $i,
                    'updated_at' => now(),
                    'name' => 'Place '.$i,
                    'class' => 'class',
                    'geom' => DB::raw("ST_GeomFromText('POINT($lon $lat)')"),
                    'tags' => json_encode(['tag' => 'value']),
                    'elevation' => rand(50, 300),
                    'score' => rand(1, 5),
                ]);
            }

            $this->usingTestData = true;
        }
    }

    /**
     * Test if the endpoint returns code 200 with correct parameters
     * @test
     */
    public function osm_places_api_returns_code_200()
    {
        $place = DB::table('places')->inRandomOrder()->first();

        $osmType = match ($place->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/places/osm/'.$osmType.'/'.$place->osm_id);

        $response->assertStatus(200);
    }

    /**
     * Test if endpoint returns code 404 with wrong osm type
     * @test
     */
    public function osm_places_api_returns_code_404_with_wrong_osm_type()
    {
        $place = DB::table('places')->inRandomOrder()->first();

        $response = $this->get('/api/v1/features/places/osm/randomvalue/'.$place->osm_id);

        $response->assertStatus(404);
    }

    /**
     * Test if endpoint returns a response with the correct structure
     * @test
     */
    public function osm_places_api_returns_correct_structure()
    {
        $place = DB::table('places')->inRandomOrder()->first();

        $osmType = match ($place->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/places/osm/'.$osmType.'/'.$place->osm_id);

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
                    ->has('properties.class')
                    ->has('properties.subclass')
                    ->has('properties.elevation')
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
