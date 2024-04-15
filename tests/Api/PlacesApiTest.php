<?php

namespace Tests\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PlacesApiTest extends TestCase
{
    use RefreshDatabase;

    private $usingTestData = false;

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

        if (!Schema::hasTable('places')) {
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
                    'name' => 'Place ' . $i,
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
     * Return code 200
     * @test
     */
    public function list_places_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/places/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the admin_area list api returns results
     * @test
     */
    public function list_places_api_returns_places()
    {
        $response = $this->get('/api/v1/features/places/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the json has the correct structure
     * @test
     */
    public function list_places_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/places/list');

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('data')
                    ->has('current_page')
                    ->has('from')
                    ->has('to')
                    ->has('first_page_url')
                    ->has('last_page')
                    ->has('last_page_url')
                    ->has('links')
                    ->has('path')
                    ->has('total')
                    ->has('per_page')
                    ->has('next_page_url')
                    ->has('prev_page_url')
                    ->has('data.0', function (AssertableJson $json) {
                        $json->has('id')
                            ->has('updated_at');
                    });
            }
        );
    }

    /**
     * Test if the http call with page parameter returns the correct results
     * @test
     */
    public function list_places_api_returns_correct_results_with_page()
    {
        $response = $this->get('/api/v1/features/places/list?page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Text if the http call with bbox parameter returns the correct results
     * @test
     */
    public function list_places_api_returns_correct_number_of_results_with_bbox()
    {
        //italy bounding box
        $bbox = '6.6273,36.619987,18.520601,47.095761';
        $response = $this->get('/api/v1/features/places/list?bbox=' . $bbox . '&testdata=' . $this->usingTestData);

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Test if the http call with score parameter returns the correct results
     * @test
     */
    public function list_places_api_returns_correct_response_with_score()
    {
        $response = $this->get('/api/v1/features/places/list?score=2');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }

    /**
     * Test if the single feature api returns the correct structure
     * @test
     */
    public function get_single_place_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/places/1');

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

    public function tearDown(): void
    {
        Schema::dropIfExists('places');

        parent::tearDown();
    }
}