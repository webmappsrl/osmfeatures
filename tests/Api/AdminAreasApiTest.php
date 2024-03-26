<?php

namespace Tests\Api;

use App\Models\AdminArea;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminAreasApiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Return code 200
     * @test
     */
    public function list_admin_area_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/admin-areas/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the admin_area list api returns results
     * @test
     */
    public function list_admin_area_api_returns_admin_area()
    {
        $response = $this->get('/api/v1/features/admin-areas/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the place single api returns code 200
     * @test
     */
    public function single_admin_area_api_returns_code_200()
    {
        //get a random place from the database
        $a = AdminArea::all()->random();
        $response = $this->get('/api/v1/features/admin-areas/'.$a->osm_id);

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the poi single api returns the correct poi
     * @test
     */
    public function single_admin_areas_api_returns_correct_area()
    {
        //get a random poi from the database
        $a = AdminArea::all()->random();
        $response = $this->get('/api/v1/features/admin-areas/'.$a->osm_id);

        //ensure that the response is not empty and has the correct structure
        $this->assertNotEmpty($response->json());
        $response->assertJsonStructure([
            'type',
            'properties',
            'geometry',
        ]);
    }
}
