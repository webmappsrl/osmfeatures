<?php

namespace Tests\Api;

use App\Console\Commands\OsmfeaturesSync;
use Tests\TestCase;
use App\Models\Pole;
use App\Models\Place;
use App\Models\AdminArea;
use App\Models\HikingRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

class SridChangeTest extends TestCase
{

    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp(); {
            $app = app();
            $command = new OsmfeaturesSync();
            $command->setLaravel($app);
            $command->osm2pgsqlSync('andorra_latest', 'storage/tests/original_andorra_latest.pbf', 'all_imports');
        }
    }

    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @throws \Exception
     * 
     */
    public function get_single_admin_area_api_returns_correct_geometry_after_changing_srid()
    {

        //get a record with a geometry
        $adminArea = AdminArea::where('geom', '!=', null)->first();

        //make a call to the api
        $response = $this->get('/api/v1/features/admin-areas/' . $adminArea->getOsmfeaturesId());

        //check if response is a geojson
        $response->assertJsonStructure(['type', 'properties', 'geometry']);

        //check if the geometry type is multipolygon
        $this->assertEquals('MultiPolygon', $response->json()['geometry']['type']);

        //check if the srid is 4326
        $geometry = json_encode($response->json()['geometry']);

        $query = "SELECT ST_SRID(ST_GeomFromGeoJSON('$geometry')) AS srid";
        $srid = DB::select($query);

        $this->assertEquals(4326, $srid[0]->srid);
    }

    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @throws \Exception
     * 
     */
    public function get_single_place_api_returns_correct_geometry_after_changing_srid()
    {

        $place = Place::where('geom', '!=', null)->first();
        $response = $this->get('/api/v1/features/places/' . $place->getOsmfeaturesId());

        $response->assertJsonStructure(['type', 'properties', 'geometry']);

        $this->assertEquals('Point', $response->json()['geometry']['type']);

        $geometry = json_encode($response->json()['geometry']);
        $query = "SELECT ST_SRID(ST_GeomFromGeoJSON('$geometry')) AS srid";
        $srid = DB::select($query);

        $this->assertEquals(4326, $srid[0]->srid);
    }


    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @throws \Exception
     * 
     */
    public function get_single_hiking_route_api_returns_correct_geometry_after_changing_srid()
    {
        $hikingRoute = HikingRoute::where('geom', '!=', null)->first();

        $response = $this->get('/api/v1/features/hiking-routes/' . $hikingRoute->getOsmfeaturesId());

        $response->assertJsonStructure(['type', 'properties', 'geometry']);


        $this->assertEquals('MultiLineString', $response->json()['geometry']['type']);

        $geometry = json_encode($response->json()['geometry']);
        $query = "SELECT ST_SRID(ST_GeomFromGeoJSON('$geometry')) AS srid";
        $srid = DB::select($query);

        $this->assertEquals(4326, $srid[0]->srid);
    }


    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @throws \Exception
     * 
     */
    public function get_single_pole_api_returns_correct_geometry_after_changing_srid()
    {
        $pole = Pole::where('geom', '!=', null)->first();
        $response = $this->get('/api/v1/features/poles/' . $pole->getOsmfeaturesId());

        $response->assertJsonStructure(['type', 'properties', 'geometry']);

        $this->assertEquals('Point', $response->json()['geometry']['type']);

        $geometry = json_encode($response->json()['geometry']);
        $query = "SELECT ST_SRID(ST_GeomFromGeoJSON('$geometry')) AS srid";
        $srid = DB::select($query);

        $this->assertEquals(4326, $srid[0]->srid);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        DB::statement('TRUNCATE TABLE admin_areas, places, hiking_routes, poles CASCADE');
    }
}
