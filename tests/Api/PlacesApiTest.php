<?php

namespace Tests\Api;

use App\Models\Place;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlacesApiTest extends TestCase
{
    use DatabaseTransactions;
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
     * Test if the places list api returns results
     * @test
     */
    public function list_places_api_returns_places()
    {

        $response = $this->get('/api/v1/features/places/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the place single api returns code 200
     * @test
     */
    public function single_poi_api_returns_code_200()
    {
        //get a random place from the database
        $place = Place::all()->random();
        $response = $this->get('/api/v1/features/places/' . $place->osm_id);

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the poi single api returns the correct poi
     * @test
     */
    public function single_place_api_returns_correct_place()
    {
        //get a random poi from the database
        $place = Place::all()->random();
        $response = $this->get('/api/v1/features/places/' . $place->osm_id);

        //ensure that the response is not empty and has the correct structure
        $this->assertNotEmpty($response->json());
        $response->assertJsonStructure([
            'type',
            'properties',
            'geometry'
        ]);
    }
}
