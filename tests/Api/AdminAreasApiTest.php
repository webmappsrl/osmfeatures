<?php

namespace Tests\Api;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\AdminArea;
use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminAreasApiTest extends TestCase
{
    use DatabaseTransactions;

    private $usingTestData = false;

    public function setUp(): void
    {
        parent::setUp(); {
            if (! Schema::hasTable('admin_areas')) {
                $seeder = new TestDBSeeder('AdminAreas');
                $seeder->run();
                $this->usingTestData = true;
            }
        }
    }

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
     * Test if the json has the correct structure
     * @test
     */
    public function list_admin_area_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/admin-areas/list');

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('data')
                    ->has('current_page')
                    ->has('from')
                    ->has('to')
                    ->has('first_page_url')
                    ->has('last_page')
                    ->has('last_page_url')
                    ->has('links')
                    ->has('path')
                    ->has('total')
                    ->has('per_page')
                    ->has('next_page_url')
                    ->has('prev_page_url')
                    ->has('data.0', function (AssertableJson $json) {
                        $json->has('id')
                            ->has('updated_at')
                            ->where('updated_at', function ($value) {
                                $date = Carbon::parse($value);

                                return $date->format('Y-m-d\TH:i:sP') === $value;
                            });
                    });
            }
        );
    }

    /**
     * Test if the http call with page parameter returns the correct results
     * @test
     */
    public function list_admin_area_api_returns_correct_results_with_page()
    {
        $response = $this->get('/api/v1/features/admin-areas/list?page=2');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Text if the http call with bbox parameter returns the correct results
     * @test
     */
    public function list_admin_area_api_returns_correct_number_of_results_with_bbox()
    {
        //italy bounding box
        $bbox = '6.6273,36.619987,18.520601,47.095761';
        $response = $this->get('/api/v1/features/admin-areas/list?bbox=' . $bbox . '&testdata=' . $this->usingTestData);

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Test if the http call with admin_level parameter returns the correct results
     * @test
     */
    public function list_admin_area_api_returns_correct_response_with_admin_level()
    {
        $response = $this->get('/api/v1/features/admin-areas/list?admin_level=8');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }

    /**
     * Test if the http call with score parameter returns the correct results
     * @test
     */
    public function list_admin_area_api_returns_correct_response_with_score()
    {
        $response = $this->get('/api/v1/features/admin-areas/list?score=3');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }

    /**
     * Test if the single feature api returns the correct structure
     * @test
     */
    public function get_single_admin_area_api_returns_correct_structure()
    {
        $adminArea = AdminArea::first();
        $response = $this->get('/api/v1/features/admin-areas/' . $adminArea->getOsmfeaturesId());

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('type')
                    ->has('properties')
                    ->has('geometry')
                    ->has('properties.osm_type')
                    ->has('properties.osm_id')
                    ->has('properties.osmfeatures_id')
                    ->has('properties.updated_at')
                    ->has('properties.name')
                    ->has('properties.admin_level')
                    ->has('properties.score')
                    ->has('properties.osm_url')
                    ->has('properties.osm_api')
                    ->has('properties.osm_tags')
                    ->has('properties.wikidata')
                    ->has('properties.wikipedia')
                    ->has('properties.wikimedia_commons');
            }
        );
    }

    /**
     * Test per il metodo intersectingGeojson con richiesta valida (senza filtri).
     * @test
     */
    public function intersecting_geojson_api_returns_feature_collection()
    {
        $payload = [
            'geojson' => [
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [0, 0],
                            [0, 1],
                            [1, 1],
                            [1, 0],
                            [0, 0]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/features/admin-areas/geojson', $payload);
        $response->assertStatus(200);
        $response->assertJsonStructure(['type', 'features']);
        $this->assertEquals('FeatureCollection', $response->json()['type']);
    }

    /**
     * Test per il metodo intersectingGeojson con richiesta valida e con filtri.
     * @test
     */
    public function intersecting_geojson_api_with_filters_returns_feature_collection()
    {
        //created a geometry that intersects with exactly one admin area
        $payload = [
            'geojson' => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'MultiLineString',
                    'coordinates' => [
                        [
                            [10.6955, 43.8535],
                            [10.697, 43.8525],
                            [10.699, 43.8515],
                            [10.701, 43.8505],
                            [10.703, 43.8495]
                        ],
                        [
                            [10.704, 43.849],
                            [10.706, 43.848],
                            [10.708, 43.847],
                            [10.71, 43.846],
                            [10.712, 43.845]
                        ]
                    ]
                ],
                'properties' => [
                    'name' => 'Hiking Route Intersecting'
                ]
            ],
            'updated_at' => '2016-01-01T00:00:00Z',
            'admin_level' => 8,
            'score' => 2
        ];

        //create a new admin area that intersects with the payload
        $lat = 43.85;
        $lon = 10.70;
        $polygon = sprintf(
            '((%.2f %.2f, %.2f %.2f, %.2f %.2f, %.2f %.2f, %.2f %.2f))',
            $lon - 0.01,
            $lat - 0.01,  // Lower Left
            $lon + 0.01,
            $lat - 0.01,  // Lower Right 
            $lon + 0.01,
            $lat + 0.01,  // Upper Right
            $lon - 0.01,
            $lat + 0.01,  // Upper Left
            $lon - 0.01,
            $lat - 0.01   // Close polygon
        );

        $adminAreaId = DB::table('admin_areas')->insertGetId([
            'name' => 'Admin Area Intersecting',
            'osm_id' => 999,
            'osm_type' => 'R',
            'geom' => DB::raw("ST_GeomFromText('MULTIPOLYGON($polygon)', 4326)"),
            'admin_level' => 8,
            'score' => 2,
            'tags' => json_encode(['wikidata' => 'value']),
            'updated_at' => Carbon::now()
        ]);

        $adminArea = AdminArea::find($adminAreaId);

        $response = $this->postJson('/api/v1/features/admin-areas/geojson', $payload);
        $response->assertStatus(200);
        $response->assertJsonStructure(['type', 'features']);
        $this->assertEquals('FeatureCollection', $response->json()['type']);
        $this->assertGreaterThan(0, count($response->json()['features']));

        //check if the adminArea is in the response
        $this->assertTrue(collect($response->json()['features'])->contains(function ($feature) use ($adminArea) {
            return $feature['properties']['osmfeatures_id'] === $adminArea->getOsmfeaturesId();
        }));
    }

    /**
     * Test per il metodo intersectingGeojson che verifica che richieste con parametri extra non ammessi restituiscano errore.
     * @test
     */
    public function intersecting_geojson_api_returns_validation_error_for_extra_parameters()
    {
        $payload = [
            'geojson' => [
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [0, 0],
                            [0, 1],
                            [1, 1],
                            [1, 0],
                            [0, 0]
                        ]
                    ]
                ]
            ],
            'unexpected' => 'valore'
        ];

        $response = $this->postJson('/api/v1/features/admin-areas/geojson', $payload);
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Validation errors']);
        $this->assertArrayHasKey('invalid_parameters', $response->json()['errors']);
    }

    /**
     * Test per il metodo intersectingGeojson che verifica che una struttura GeoJSON non valida restituisca un errore.
     * @test
     */
    public function intersecting_geojson_api_returns_validation_error_for_invalid_geojson_structure()
    {
        // Inviare "geojson" senza la chiave "geometry"
        $payload = [
            'geojson' => []
        ];

        $response = $this->postJson('/api/v1/features/admin-areas/geojson', $payload);
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Validation errors']);
        $this->assertTrue(isset($response->json()['errors']['geojson']));
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('admin_areas');
        parent::tearDown();
    }
}
