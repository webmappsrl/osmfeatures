<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\hikingRoute;
use Database\Seeders\TestDBSeeder;

class SridChangeHikingRoutes2Test extends TestCase
{

    protected $geometry3857;

    protected $geometry4326;

    protected $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->geometry3857 = $this->get3857sridGeometry();

        //wipe the database
        $this->artisan('db:wipe');

        $this->geometry4326 = $this->get4326sridGeometry();
    }

    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_hiking_route_api_returns_correct_geometry_after_changing_srid()
    {

        // Verifica se la risposta è un geojson
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);

        // Verifica se il tipo di geometria è multipolygon
        $this->assertEquals('MultiLineString', $this->response->json()['geometry']['type']);

        //verifica che la geometria sia invariata
        $this->assertEquals($this->geometry3857, $this->geometry4326);
    }

    private function get3857sridGeometry()
    {
        //call the srid3857 seeder
        $seeder = new TestDBSeeder('srid3857');
        $seeder->run();

        $hikingRoute = HikingRoute::where('osm_id', 4174475)->first(); //https://www.openstreetmap.org/relation/4174475
        $this->response = $this->get('/api/v1/features/hiking-routes/' . $hikingRoute->getOsmfeaturesId());

        $geometry3857 = json_encode($this->response->json()['geometry']);

        return $geometry3857;
    }

    private function get4326sridGeometry()
    {
        //call the srid 4326 seeder
        $seeder = new TestDBSeeder('srid4326');
        $seeder->run();

        $hikingRoute = HikingRoute::where('osm_id', 4174475)->first(); //https://www.openstreetmap.org/relation/4174475

        $this->response = $this->get('/api/v1/features/hiking-routes/' . $hikingRoute->getOsmfeaturesId());

        $geometry4326 = json_encode($this->response->json()['geometry']);

        return $geometry4326;
    }
}