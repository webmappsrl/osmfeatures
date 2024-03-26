<?php

namespace Tests\Api;

use App\Models\Poi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PoisApiTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * Return code 200
     * @test
     */
    public function list_pois_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/pois/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the poi list api returns results
     * @test
     */
    public function list_pois_api_returns_pois()
    {

        $response = $this->get('/api/v1/features/pois/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the poi single api returns code 200
     * @test
     */
    public function single_poi_api_returns_code_200()
    {
        //get a random poi from the database
        $poi = Poi::all()->random();
        $response = $this->get('/api/v1/features/pois/' . $poi->osm_id);

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the poi single api returns the correct poi
     * @test
     */
    public function single_poi_api_returns_correct_poi()
    {
        //get a random poi from the database
        $poi = Poi::all()->random();
        $response = $this->get('/api/v1/features/pois/' . $poi->osm_id);

        //ensure that the response is not empty and has the correct structure
        $this->assertNotEmpty($response->json());
        $response->assertJsonStructure([
            'type',
            'properties',
            'geometry'
        ]);
    }
}
