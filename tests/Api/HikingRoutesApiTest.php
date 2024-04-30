<?php

namespace Tests\Api;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HikingRoutesApiTest extends TestCase
{
    use RefreshDatabase;

    private $usingTestData = false;

    public function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable('hiking_routes')) {
            $seeder = new TestDBSeeder('HikingRoutes');
            $seeder->run();
            $this->usingTestData = true;
        }
    }

    /**
     * Return code 200
     * @test
     */
    public function list_hiking_routes_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the admin_area list api returns results
     * @test
     */
    public function list_hiking_routes_api_returns_hiking_routes()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the json has the correct structure
     * @test
     */
    public function list_hiking_routes_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list');

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
                            ->has('updated_at')
                            ->where('updated_at', function ($value) {
                                $date = Carbon::parse($value);

                                return $date->format('Y-m-d\TH:i:sP') === $value;
                            });
                    });
            }
        );
    }

    /**
     * Test if the http call with page parameter returns the correct results
     * @test
     */
    public function list_hiking_routes_api_returns_correct_results_with_page()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list?page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Text if the http call with bbox parameter returns the correct results
     * @test
     */
    public function list_hiking_routes_api_returns_correct_number_of_results_with_bbox()
    {
        //italy bounding box
        $bbox = '6.6273,36.619987,18.520601,47.095761';
        $response = $this->get('/api/v1/features/hiking-routes/list?bbox=' . $bbox . '&testdata=' . $this->usingTestData);

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Test if the http call with score parameter returns the correct results
     * @test
     */
    public function list_hiking_routes_api_returns_correct_number_of_results_with_score()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list?score=1');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }

    /**
     * Test if the single feature api returns the correct structure
     * @test
     */
    public function get_single_hiking_route_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/hiking-routes/1');

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
                    ->whereType('properties.symbol_it', 'string')
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
                    ->has('properties.wikipedia')
                    ->has('properties.osm_url')
                    ->has('properties.osm_api');
            }
        );
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('hiking_routes');

        parent::tearDown();
    }
}
