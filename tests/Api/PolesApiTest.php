<?php

namespace Tests\Feature\Api;

use App\Models\Pole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PolesApiTest extends TestCase
{
    use DatabaseTransactions;

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
     * Test if the poles list api returns results
     * @test
     */
    public function list_poles_api_returns_poles()
    {
        $response = $this->get('/api/v1/features/poles/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the place single api returns code 200
     * @test
     */
    public function single_poles_api_returns_code_200()
    {
        //get a random place from the database
        $pole = Pole::all()->random();
        $response = $this->get('/api/v1/features/poles/'.$pole->osm_id);

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the poi single api returns the correct poi
     * @test
     */
    public function single_pole_api_returns_correct_pole()
    {
        //get a random poi from the database
        $pole = Pole::all()->random();
        $response = $this->get('/api/v1/features/poles/'.$pole->osm_id);

        //ensure that the response is not empty and has the correct structure
        $this->assertNotEmpty($response->json());
        $response->assertJsonStructure([
            'type',
            'properties',
            'geometry',
        ]);
    }
}
