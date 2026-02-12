<?php

namespace Tests\Api;

use Database\Seeders\TestDBSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->ensureSearchTablesExist();
        $this->truncateSearchTables();
    }

    /**
     * @test
     */
    public function search_api_with_default_models_and_radius_returns_supported_models()
    {
        $lat = 43.85;
        $lon = 10.70;

        $adminAreaId = $this->insertAdminAreaAroundPoint(900001, $lat, $lon);
        $placeId = $this->insertPlaceAtPoint(900002, $lat, $lon);
        $poleId = $this->insertPoleAtPoint(900003, $lat, $lon);
        $hikingRouteOsmId = $this->insertHikingRouteNearPoint(900004, $lat, $lon);

        $response = $this->get('/api/v1/features/search?lat=' . $lat . '&lon=' . $lon . '&radius=500');

        $response->assertStatus(200);
        $this->assertEquals('FeatureCollection', $response->json('type'));

        $features = collect($response->json('features'));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $placeId));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $poleId));
        $this->assertTrue($features->contains(
            fn(array $feature): bool => ($feature['properties']['model'] ?? null) === 'hiking-routes'
                && (int) ($feature['properties']['osm_id'] ?? 0) === $hikingRouteOsmId
        ));
        $this->assertFalse($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $adminAreaId));
    }

    /**
     * @test
     */
    public function search_api_ignores_models_with_missing_tables_when_models_parameter_is_not_passed()
    {
        $lat = 43.85;
        $lon = 10.70;

        $placeId = $this->insertPlaceAtPoint(900101, $lat, $lon);
        $poleId = $this->insertPoleAtPoint(900102, $lat, $lon);

        Schema::dropIfExists('hiking_routes');

        $response = $this->get('/api/v1/features/search?lat=' . $lat . '&lon=' . $lon . '&radius=500');

        $response->assertStatus(200);
        $this->assertEquals('FeatureCollection', $response->json('type'));

        $features = collect($response->json('features'));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $placeId));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $poleId));
        $this->assertFalse($features->contains(fn(array $feature): bool => ($feature['properties']['model'] ?? null) === 'hiking-routes'));
    }

    /**
     * @test
     */
    public function search_api_with_default_models_and_point_only_returns_admin_areas()
    {
        $lat = 43.85;
        $lon = 10.70;

        $adminAreaId = $this->insertAdminAreaAroundPoint(900011, $lat, $lon);
        $placeId = $this->insertPlaceAtPoint(900012, $lat, $lon);

        $response = $this->get('/api/v1/features/search?lat=' . $lat . '&lon=' . $lon);

        $response->assertStatus(200);
        $this->assertEquals('FeatureCollection', $response->json('type'));

        $features = collect($response->json('features'));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $adminAreaId));
        $this->assertFalse($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $placeId));
    }

    /**
     * @test
     */
    public function search_api_returns_validation_error_for_invalid_model()
    {
        $response = $this->get('/api/v1/features/search?models=invalid-model&bbox=10.69,43.84,10.71,43.86');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Validation errors']);
    }

    /**
     * @test
     */
    public function search_api_returns_validation_error_for_unsupported_mode_on_selected_models()
    {
        $response = $this->get('/api/v1/features/search?models=places&lat=43.85&lon=10.70');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Validation errors']);
    }

    /**
     * @test
     */
    public function search_api_returns_results_with_bbox_for_requested_models()
    {
        $bbox = '10.69,43.84,10.71,43.86';

        $placeId = $this->insertPlaceAtPoint(900021, 43.85, 10.70);
        $poleId = $this->insertPoleAtPoint(900022, 43.85, 10.70);
        $hikingRouteOsmId = $this->insertHikingRouteNearPoint(900023, 43.85, 10.70);

        $response = $this->get('/api/v1/features/search?models=places,poles,hiking-routes&bbox=' . $bbox);

        $response->assertStatus(200);

        $features = collect($response->json('features'));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $placeId));
        $this->assertTrue($features->contains(fn(array $feature): bool => $feature['properties']['osmfeatures_id'] === $poleId));
        $this->assertTrue($features->contains(
            fn(array $feature): bool => ($feature['properties']['model'] ?? null) === 'hiking-routes'
                && (int) ($feature['properties']['osm_id'] ?? 0) === $hikingRouteOsmId
        ));
    }

    /**
     * @test
     */
    public function search_api_caps_results_to_50_per_model()
    {
        for ($i = 0; $i < 70; $i++) {
            $this->insertPlaceAtPoint(910000 + $i, 43.85, 10.70);
        }

        $response = $this->get('/api/v1/features/search?models=places&bbox=10.69,43.84,10.71,43.86');

        $response->assertStatus(200);
        $features = collect($response->json('features'));

        $this->assertCount(50, $features);
        $this->assertTrue($features->every(
            fn(array $feature): bool => ($feature['properties']['model'] ?? null) === 'places'
        ));
    }

    /**
     * @test
     */
    public function search_api_returns_validation_error_without_spatial_parameters()
    {
        $response = $this->get('/api/v1/features/search');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Validation errors']);
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('admin_areas');
        Schema::dropIfExists('places');
        Schema::dropIfExists('poles');
        Schema::dropIfExists('hiking_routes');
        parent::tearDown();
    }

    private function ensureSearchTablesExist(): void
    {
        if (!Schema::hasTable('admin_areas')) {
            (new TestDBSeeder('AdminAreas'))->run();
        }

        if (!Schema::hasTable('places')) {
            (new TestDBSeeder('Places'))->run();
        }

        if (!Schema::hasTable('poles')) {
            (new TestDBSeeder('Poles'))->run();
        }

        if (!Schema::hasTable('hiking_routes')) {
            (new TestDBSeeder('HikingRoutes'))->run();
        }
    }

    private function truncateSearchTables(): void
    {
        DB::statement('TRUNCATE TABLE admin_areas RESTART IDENTITY');
        DB::statement('TRUNCATE TABLE places RESTART IDENTITY');
        DB::statement('TRUNCATE TABLE poles RESTART IDENTITY');
        DB::statement('TRUNCATE TABLE hiking_routes RESTART IDENTITY');
    }

    private function insertAdminAreaAroundPoint(int $osmId, float $lat, float $lon): string
    {
        $polygon = sprintf(
            'MULTIPOLYGON(((%.5f %.5f, %.5f %.5f, %.5f %.5f, %.5f %.5f, %.5f %.5f)))',
            $lon - 0.01,
            $lat - 0.01,
            $lon + 0.01,
            $lat - 0.01,
            $lon + 0.01,
            $lat + 0.01,
            $lon - 0.01,
            $lat + 0.01,
            $lon - 0.01,
            $lat - 0.01
        );

        DB::table('admin_areas')->insert([
            'name' => 'Search AdminArea',
            'osm_id' => $osmId,
            'osm_type' => 'R',
            'geom' => DB::raw("ST_GeomFromText('$polygon', 4326)"),
            'admin_level' => 8,
            'score' => 2,
            'tags' => json_encode(['wikidata' => 'Q1']),
            'updated_at' => now(),
        ]);

        return 'R' . $osmId;
    }

    private function insertPlaceAtPoint(int $osmId, float $lat, float $lon): string
    {
        DB::table('places')->insert([
            'osm_type' => 'N',
            'osm_id' => $osmId,
            'updated_at' => now(),
            'name' => 'Search Place',
            'class' => 'amenity',
            'subclass' => 'shelter',
            'geom' => DB::raw("ST_GeomFromText('POINT($lon $lat)', 4326)"),
            'tags' => json_encode(['wikidata' => 'Q2']),
            'elevation' => 1200,
            'score' => 3,
        ]);

        return 'N' . $osmId;
    }

    private function insertPoleAtPoint(int $osmId, float $lat, float $lon): string
    {
        DB::table('poles')->insert([
            'osm_type' => 'N',
            'osm_id' => $osmId,
            'updated_at' => now(),
            'name' => 'Search Pole',
            'tags' => json_encode(['wikidata' => 'Q3']),
            'geom' => DB::raw("ST_GeomFromText('POINT($lon $lat)', 4326)"),
            'ref' => 'P1',
            'ele' => '1100',
            'destination' => 'Rifugio',
            'support' => 'metal',
            'score' => 4,
        ]);

        return 'N' . $osmId;
    }

    private function insertHikingRouteNearPoint(int $osmId, float $lat, float $lon): int
    {
        $line = sprintf(
            'MULTILINESTRING((%.5f %.5f, %.5f %.5f))',
            $lon - 0.005,
            $lat - 0.005,
            $lon + 0.005,
            $lat + 0.005
        );

        DB::table('hiking_routes')->insert([
            'name' => 'Search Hiking Route',
            'osm_id' => $osmId,
            'osm_type' => 'R',
            'updated_at_osm' => now(),
            'updated_at' => now(),
            'score' => 2,
            'geom' => DB::raw("ST_GeomFromText('$line', 4326)"),
            'members' => json_encode([]),
            'tags' => json_encode(['wikidata' => 'Q4']),
        ]);

        return $osmId;
    }
}
