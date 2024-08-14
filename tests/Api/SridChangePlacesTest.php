<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\Pole;
use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\DB;

class SridChangePlacesTest extends TestCase
{
    protected $response;

    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_place_api_returns_correct_geometry_4326()
    {
        $this->artisan('db:wipe');

        //call the srid seeder
        $seeder = new TestDBSeeder('srid4326');
        $seeder->run();

        $this->response = $this->get('/api/v1/features/places/W704967428'); //https://www.openstreetmap.org/way/704967428

        $filepath = storage_path('tests/chiesa_di_san_giovanni_battista.geojson');
        $geojson = json_decode(file_get_contents($filepath), true)['features'][0]['geometry'];

        //get the centroid of the polygon from the geojson with postgis
        $centroidQuery = 'SELECT ST_X(ST_Centroid(ST_GeomFromGeoJSON(:geojson))) as longitude, ST_Y(ST_Centroid(ST_GeomFromGeoJSON(:geojson))) as latitude';
        $centroid = DB::select($centroidQuery, ['geojson' => json_encode($geojson)]);
        $geojsonLon = $centroid[0]->longitude;
        $geojsonLat = $centroid[0]->latitude;

        // Verifica se la risposta è un geojson
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);

        // Verifica se il tipo di geometria è point
        $this->assertEquals('Point', $this->response->json()['geometry']['type']);

        $longitude = $this->response->json()['geometry']['coordinates'][0];
        $latitude = $this->response->json()['geometry']['coordinates'][1];

        //verifica che la geometria sia invariata
        $this->assertTrue($longitude >= $geojsonLon - 0.00001, $longitude <= $geojsonLon + 0.00001);
        $this->assertTrue($latitude >= $geojsonLat - 0.00001, $latitude <= $geojsonLat + 0.00001);
    }

    /**
     * Test if the geometry output is the same changing SRID to 3857 to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_place_api_returns_correct_geometry_3857()
    {
        $this->artisan('db:wipe');

        //call the srid seeder
        $seeder = new TestDBSeeder('srid3857');
        $seeder->run();

        $this->response = $this->get('/api/v1/features/places/W704967428'); //https://www.openstreetmap.org/way/704967428

        $filepath = storage_path('tests/chiesa_di_san_giovanni_battista.geojson');
        $geojson = json_decode(file_get_contents($filepath), true)['features'][0]['geometry'];

        //get the centroid of the polygon from the geojson with postgis
        $centroidQuery = 'SELECT ST_X(ST_Centroid(ST_GeomFromGeoJSON(:geojson))) as longitude, ST_Y(ST_Centroid(ST_GeomFromGeoJSON(:geojson))) as latitude';
        $centroid = DB::select($centroidQuery, ['geojson' => json_encode($geojson)]);
        $geojsonLon = $centroid[0]->longitude;
        $geojsonLat = $centroid[0]->latitude;

        // Verifica se la risposta è un geojson
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);

        // Verifica se il tipo di geometria è point
        $this->assertEquals('Point', $this->response->json()['geometry']['type']);

        $longitude = $this->response->json()['geometry']['coordinates'][0];
        $latitude = $this->response->json()['geometry']['coordinates'][1];

        //verifica che la geometria sia invariata
        $this->assertTrue($longitude >= $geojsonLon - 0.00001, $longitude <= $geojsonLon + 0.00001);
        $this->assertTrue($latitude >= $geojsonLat - 0.00001, $latitude <= $geojsonLat + 0.00001);
    }
}
