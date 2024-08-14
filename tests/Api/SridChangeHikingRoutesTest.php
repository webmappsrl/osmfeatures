<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\Pole;
use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\DB;

class SridChangeHikingRoutesTest extends TestCase
{
    protected $response;

    /**
     * Test if the geometry output is the same changing SRID to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_hiking_route_api_returns_correct_geometry_4326()
    {
        $this->artisan('db:wipe');

        //Call the srid 4326 seeder
        $seeder = new TestDBSeeder('srid4326');
        $seeder->run();

        // Get the API response
        $this->response = $this->get('/api/v1/features/hiking-routes/R4174475'); //https://www.openstreetmap.org/relation/4174475

        // Convert MultiLineString to LineString using PostGIS
        $linestringGeomQuery = 'SELECT ST_AsText(ST_LineMerge(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326))) as geometry';
        $linestringGeom = DB::select($linestringGeomQuery, ['geometry' => json_encode($this->response->json()['geometry'])])[0]->geometry;

        // Transform the LineString geometry to GeoJSON
        $toGeojsonQuery = 'SELECT ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_GeomFromText(:geometry), 4326), 4326)) as geometry';
        $toGeojson = DB::select($toGeojsonQuery, ['geometry' => $linestringGeom])[0]->geometry;
        $linestring = json_decode($toGeojson, true);

        // Load the GeoJSON file
        $geojsonPath = storage_path('tests/sentiero_pisa.geojson');
        $geojson = json_decode(file_get_contents($geojsonPath), true);
        $geojsonCoordinates = $geojson['features'][0]['geometry']['coordinates'];

        // Verifica se la risposta è un GeoJSON
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);
        $this->assertEquals('MultiLineString', $this->response->json()['geometry']['type']);
        $this->assertCount(count($linestring['coordinates']), $geojsonCoordinates);

        // Compare each line string's coordinates
        foreach ($linestring['coordinates'] as $key => $value) {
            $this->assertEquals($value, $geojsonCoordinates[$key]);
        }
    }



    /**
     * Test if the geometry output is the same changing SRID to 3857
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_hiking_route_api_returns_correct_geometry_3857()
    {
        $this->artisan('db:wipe');

        //Call the srid 3857 seeder (hiking routes projection was 4326)
        $seeder = new TestDBSeeder('srid3857');
        $seeder->run();

        // Get the API response
        $this->response = $this->get('/api/v1/features/hiking-routes/R4174475'); //https://www.openstreetmap.org/relation/4174475
        $apiCoordinates = $this->response->json()['geometry']['coordinates'];

        // Convert MultiLineString to LineString using PostGIS
        $linestringGeomQuery = 'SELECT ST_AsText(ST_LineMerge(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326))) as geometry';
        $linestringGeom = DB::select($linestringGeomQuery, ['geometry' => json_encode($this->response->json()['geometry'])])[0]->geometry;

        // Transform the LineString geometry to GeoJSON
        $toGeojsonQuery = 'SELECT ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_GeomFromText(:geometry), 4326), 4326)) as geometry';
        $toGeojson = DB::select($toGeojsonQuery, ['geometry' => $linestringGeom])[0]->geometry;
        $linestring = json_decode($toGeojson, true);

        // Load the GeoJSON file
        $geojsonPath = storage_path('tests/sentiero_pisa.geojson');
        $geojson = json_decode(file_get_contents($geojsonPath), true);
        $geojsonCoordinates = $geojson['features'][0]['geometry']['coordinates'];

        // Verifica se la risposta è un GeoJSON
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);
        $this->assertEquals('MultiLineString', $this->response->json()['geometry']['type']);
        $this->assertCount(count($linestring['coordinates']), $geojsonCoordinates);

        // Compare each line string's coordinates
        foreach ($linestring['coordinates'] as $key => $value) {
            $this->assertEquals($value, $geojsonCoordinates[$key]);
        }
    }
}
