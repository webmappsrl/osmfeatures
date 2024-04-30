<?php

namespace Tests\Feature\Api;

use App\Models\Pole;
use Database\Seeders\TestDBSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PolesApiTest extends TestCase
{
    use RefreshDatabase;

    private $usingTestData = false;

    public function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable('poles')) {
            $seeder = new TestDBSeeder('Poles');
            $seeder->run();
            $this->usingTestData = true;
        }
    }

    /**
     * Return code 200
     * @test
     */
    public function list_poles_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/poles/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the admin_area list api returns results
     * @test
     */
    public function list_poles_api_returns_poles()
    {
        $response = $this->get('/api/v1/features/poles/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the json has the correct structure
     * @test
     */
    public function list_poles_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/poles/list');

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
    public function list_poles_api_returns_correct_results_with_page()
    {
        $response = $this->get('/api/v1/features/poles/list?page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Text if the http call with bbox parameter returns the correct results
     * @test
     */
    public function list_poles_api_returns_correct_number_of_results_with_bbox()
    {
        //italy bounding box
        $bbox = '6.6273,36.619987,18.520601,47.095761';
        $response = $this->get('/api/v1/features/poles/list?bbox=' . $bbox . '&testdata=' . $this->usingTestData);

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Test if the http call with score parameter returns the correct results
     * @test
     */
    public function list_poles_api_returns_correct_response_with_score()
    {
        $response = $this->get('/api/v1/features/poles/list?score=1');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }

    /**
     * Test if the single feature api returns the correct structure
     * @test
     */
    public function get_pole_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/poles/1');

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

    public function tearDown(): void
    {
        Schema::dropIfExists('poles');

        parent::tearDown();
    }
}
