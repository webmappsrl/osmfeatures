<?php

namespace Tests\Api;

use App\Models\Place;
use Database\Seeders\TestDBSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PlacesApiTest extends TestCase
{
    use DatabaseTransactions;

    private $usingTestData = false;

    public function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable('places')) {
            $seeder = new TestDBSeeder('Places');
            $seeder->run();
            $this->usingTestData = true;
        }

        if (!Schema::hasTable('enrichments')) {
            $seeder = new TestDBSeeder('Enrichments');
            $seeder->run();
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
        $place = Place::first();
        $response = $this->get('/api/v1/features/places/' . $place->getOsmFeaturesId());

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('type')
                    ->has('properties')
                    ->has('geometry')
                    ->has('properties.osm_type')
                    ->has('properties.osm_id')
                    ->has('properties.osmfeatures_id')
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
                    ->has('properties.wikimedia_commons')
                    ->has('properties.enrichments');
            }
        );
    }

    /**
     * Test if the http call with correct parameter returns the correct results
     * @test
     */
    public function distance_places_api_returns_correct_response_with_correct_parameters()
    {
        // Test if the Places API returns the correct response when given the correct parameters
        $response = $this->get('/api/v1/features/places/10.494953/46.179482/1234567');

        // Assert that the response has a status code of 200
        $response->assertStatus(200);
        // Assert that the response JSON array is not empty
        $this->assertNotEquals(0, count($response->json()));
    }

    /**
     * Test if the http call with missing parameter return a bad request
     * @test
     */
    public function distance_places_api_returns_404_with_missing_parameter()
    {
        // Test if the Places API returns a bad request when missing the distance parameter
        $response = $this->get('/api/v1/features/places/10.494953/46.179482');

        // Assert that the response has a status code of 400
        $response->assertStatus(404);
    }

    /**
     * Test if the http call with wrong latitude parameter return a bad request
     * @test
     */
    public function distance_places_api_returns_bad_request_with_wrong_parameter()
    {
        // Test if the Places API returns a bad request when given a wrong latitude parameter
        $response = $this->get('/api/v1/features/places/10.494953/failing_parameter/1000');

        // Assert that the response has a status code of 400
        $response->assertStatus(400);
        // Assert that the response JSON contains the expected error message
        $response->assertJson([
            'message' => 'Invalid latitude parameter',
        ]);
    }

    /**
     * Test if the http call with correct parameters return correct json structure
     * @test
     */
    public function distance_places_api_returns_correct_json_structure()
    {
        // Test if the Places API returns the correct JSON structure when given the correct parameters
        $response = $this->get('/api/v1/features/places/10.494953/46.179482/1234567');
        $json = $response->json();

        // Assert that the response JSON array has the expected keys
        $keys = ['osmfeatures_id', 'name', 'class', 'subclass', 'elevation', 'distance'];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $json[0]);
        }
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('places');

        parent::tearDown();
    }
}
