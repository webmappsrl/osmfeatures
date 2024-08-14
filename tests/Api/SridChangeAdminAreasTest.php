<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Models\Pole;
use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\DB;

class SridChangeAdminAreasTest extends TestCase
{
    protected $response;

    /**
     * Test if the geometry output is the same changing SRID to 4326
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_admin_area_api_returns_correct_geometry_4326()
    {
        $this->artisan('db:wipe');

        // Call the srid 4326 seeder
        $seeder = new TestDBSeeder('srid4326');
        $seeder->run();

        // Get the API response
        $this->response = $this->get('/api/v1/features/admin-areas/R42592');

        // Convert MultiPolygon to Polygon
        $polygonGeomQuery = 'SELECT ST_AsText(ST_Union(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326))) as geometry';
        $polygonGeom = DB::select($polygonGeomQuery, ['geometry' => json_encode($this->response->json()['geometry'])])[0]->geometry;

        // Transform the Polygon geometry to GeoJSON
        $toGeojsonQuery = 'SELECT ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_GeomFromText(:geometry), 4326), 4326)) as geometry';
        $toGeojson = DB::select($toGeojsonQuery, ['geometry' => $polygonGeom])[0]->geometry;
        $polygon = json_decode($toGeojson, true);

        // Load the GeoJSON file
        $geojsonPath = storage_path('tests/calci.geojson');
        $geojson = json_decode(file_get_contents($geojsonPath), true);
        $geojsonCoordinates = $geojson['features'][0]['geometry']['coordinates'];

        // Check if the response has the correct structure
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);
        $this->assertEquals('MultiPolygon', $this->response->json()['geometry']['type']);

        // Convert coordinates to sets for comparison
        $polygonCoordinatesSet = $this->coordinatesToSet($polygon['coordinates'][0]);
        $geojsonCoordinatesSet = $this->coordinatesToSet($geojsonCoordinates[0]);

        // Set the tolerance value (1,1 meters)
        $tolerance = 0.00001;

        // Check if all polygon coordinates are within tolerance of geojson coordinates
        foreach ($polygonCoordinatesSet as $coordinate) {
            $this->assertTrue(
                $this->coordinateIsInRange($coordinate, $geojsonCoordinatesSet, $tolerance),
                'Coordinate ' . json_encode($coordinate) . ' not found in GeoJSON coordinates within tolerance.'
            );
        }

        // Optionally check if geojson coordinates contain all polygon coordinates
        foreach ($geojsonCoordinatesSet as $coordinate) {
            $this->assertTrue(
                $this->coordinateIsInRange($coordinate, $polygonCoordinatesSet, $tolerance),
                'Coordinate ' . json_encode($coordinate) . ' not found in Polygon coordinates within tolerance.'
            );
        }
    }



    /**
     * Test if the geometry output is the same changing SRID to 3857
     * @test
     * @group srid-change
     * @throws \Exception
     */
    public function get_single_admin_area_api_returns_correct_geometry_3857()
    {
        $this->artisan('db:wipe');

        // Call the srid 4326 seeder
        $seeder = new TestDBSeeder('srid3857');
        $seeder->run();

        // Get the API response
        $this->response = $this->get('/api/v1/features/admin-areas/R42592');

        // Convert MultiPolygon to Polygon using PostGIS
        $polygonGeomQuery = 'SELECT ST_AsText(ST_Union(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326))) as geometry';
        $polygonGeom = DB::select($polygonGeomQuery, ['geometry' => json_encode($this->response->json()['geometry'])])[0]->geometry;

        // Transform the Polygon geometry to GeoJSON
        $toGeojsonQuery = 'SELECT ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_GeomFromText(:geometry), 4326), 4326)) as geometry';
        $toGeojson = DB::select($toGeojsonQuery, ['geometry' => $polygonGeom])[0]->geometry;
        $polygon = json_decode($toGeojson, true);

        // Load the GeoJSON file
        $geojsonPath = storage_path('tests/calci.geojson');
        $geojson = json_decode(file_get_contents($geojsonPath), true);
        $geojsonCoordinates = $geojson['features'][0]['geometry']['coordinates'];

        // Verifica se la risposta è un GeoJSON
        $this->response->assertJsonStructure(['type', 'properties', 'geometry']);
        $this->assertEquals('MultiPolygon', $this->response->json()['geometry']['type']);

        // Convert coordinates to sets for comparison
        $polygonCoordinatesSet = $this->coordinatesToSet($polygon['coordinates'][0]);
        $geojsonCoordinatesSet = $this->coordinatesToSet($geojsonCoordinates[0]);

        // Tolerance value (1,1 meters)
        $tolerance = 0.00001;

        // Check if all polygon coordinates are within tolerance of geojson coordinates
        foreach ($polygonCoordinatesSet as $coordinate) {
            $this->assertTrue(
                $this->coordinateIsInRange($coordinate, $geojsonCoordinatesSet, $tolerance),
                'Coordinate ' . json_encode($coordinate) . ' not found in GeoJSON coordinates within tolerance.'
            );
        }

        // Optionally check if geojson coordinates contain all polygon coordinates
        foreach ($geojsonCoordinatesSet as $coordinate) {
            $this->assertTrue(
                $this->coordinateIsInRange($coordinate, $polygonCoordinatesSet, $tolerance),
                'Coordinate ' . json_encode($coordinate) . ' not found in Polygon coordinates within tolerance.'
            );
        }
    }

    /**
     * Convert coordinates array to a set of unique points.
     *
     * @param array $coordinates
     * @return array
     */
    protected function coordinatesToSet(array $coordinates)
    {
        // Flatten nested arrays of coordinates if needed
        $flatCoordinates = $this->flattenCoordinates($coordinates);

        // Return array of unique points
        return $flatCoordinates;
    }

    /**
     * Flatten nested coordinates arrays.
     *
     * @param array $coordinates
     * @return array
     */
    protected function flattenCoordinates(array $coordinates)
    {
        $result = [];
        foreach ($coordinates as $coordinate) {
            if (is_array($coordinate[0])) {
                $result = array_merge($result, $this->flattenCoordinates($coordinate));
            } else {
                $result[] = $coordinate;
            }
        }
        return $result;
    }

    /**
     * Check if a coordinate is within the tolerance range of any coordinates in a set.
     *
     * @param array $coordinate
     * @param array $coordinatesSet
     * @param float $tolerance
     * @return bool
     */
    protected function coordinateIsInRange(array $coordinate, array $coordinatesSet, float $tolerance)
    {
        foreach ($coordinatesSet as $otherCoordinate) {
            if (
                abs($coordinate[0] - $otherCoordinate[0]) <= $tolerance &&
                abs($coordinate[1] - $otherCoordinate[1]) <= $tolerance
            ) {
                return true;
            }
        }
        return false;
    }
}
