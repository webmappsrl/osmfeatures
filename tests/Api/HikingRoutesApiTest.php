<?php

namespace Tests\Api;

use App\Models\HikingRoute;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HikingRoutesApiTest extends TestCase
{
    use DatabaseTransactions;
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
     * Test if the hiking_routes list api returns results
     * @test
     */
    public function list_hiking_routes_api_returns_hiking_routes()
    {

        $response = $this->get('/api/v1/features/hiking-routes/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the place single api returns code 200
     * @test
     */
    public function single_hiking_routes_api_returns_code_200()
    {
        //get a random place from the database
        $hr = HikingRoute::all()->random();
        $response = $this->get('/api/v1/features/hiking-routes/' . $hr->osm_id);

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the poi single api returns the correct poi
     * @test
     */
    public function single_hiking_routess_api_returns_correct_area()
    {
        //get a random poi from the database
        $hr = HikingRoute::all()->random();
        $response = $this->get('/api/v1/features/hiking-routes/' . $hr->osm_id);

        //ensure that the response is not empty and has the correct structure
        $this->assertNotEmpty($response->json());
        $response->assertJsonStructure([
            'type',
            'properties',
            'geometry'
        ]);
    }
}
