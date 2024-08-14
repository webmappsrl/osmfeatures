<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\Pole;
use Database\Seeders\TestDBSeeder;

class SridChangePolesTest extends TestCase
{

    protected $response;

    /**
     * Test if the geometry output is the same changing SRID from 3857 to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_pole_api_returns_correct_geometry_4326()
    {
        $this->artisan('db:wipe');

        //chiama il seeder
        $seeder = new TestDBSeeder('srid4326');
        $seeder->run();

        $this->response = $this->get('/api/v1/features/poles/N4317322863'); //https://www.openstreetmap.org/node/4317322863

        // Verifica se la risposta è un geojson
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);

        // Verifica se il tipo di geometria è point
        $this->assertEquals('Point', $this->response->json()['geometry']['type']);

        $longitude = $this->response->json()['geometry']['coordinates'][0];
        $latitude = $this->response->json()['geometry']['coordinates'][1];

        //verifica che la geometria sia invariata
        $this->assertTrue($longitude >= 10.4925598, $longitude <= 10.4925599); // 10.4925598
        $this->assertTrue($latitude >= 43.7519477, $latitude <= 43.751948); // 43.7519477
    }

    /**
     * Test if the geometry output is the same changing SRID to 3857 to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_pole_api_returns_correct_geometry_3857()
    {
        $this->artisan('db:wipe');

        //chiama il seeder
        $seeder = new TestDBSeeder('srid3857');
        $seeder->run();

        $this->response = $this->get('/api/v1/features/poles/N4317322863'); //https://www.openstreetmap.org/node/4317322863

        // Verifica se la risposta è un geojson
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);

        // Verifica se il tipo di geometria è point
        $this->assertEquals('Point', $this->response->json()['geometry']['type']);

        $longitude = $this->response->json()['geometry']['coordinates'][0];
        $latitude = $this->response->json()['geometry']['coordinates'][1];

        //verifica che la geometria sia invariata
        $this->assertTrue($longitude >= 10.4925598, $longitude <= 10.4925599); // 10.4925598
        $this->assertTrue($latitude >= 43.7519477, $latitude <= 43.751948); // 43.7519477
    }
}
