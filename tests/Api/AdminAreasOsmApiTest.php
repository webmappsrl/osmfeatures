<?php

namespace Tests\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AdminAreasOsmApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('admin_areas')) {
            Schema::create(
                'admin_areas',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->bigInteger('osm_id');
                    $table->string('osm_type');
                    $table->geometry('geom');
                    $table->integer('admin_level');
                    $table->integer('score');
                    $table->jsonb('tags')->nullable();
                    $table->timestamps();
                }
            );
            //create 200 admin areas
            for ($i = 0; $i < 200; $i++) {
                $lat = rand(3600, 4700) / 100;
                $lon = rand(600, 1900) / 100;

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
                    $lat - 0.01   // Closing at start point to complete the loop
                );

                DB::table('admin_areas')->insert([
                    'name' => 'Admin Area '.$i,
                    'osm_id' => $i,
                    'osm_type' => 'R',
                    'geom' => DB::raw("ST_GeomFromText('MULTIPOLYGON($polygon)')"),
                    'admin_level' => rand(1, 11),
                    'score' => rand(1, 4),
                    'tags' => json_encode(['tag' => 'value']),
                ]);
            }
        }
    }

    /**
     * Test if the endpoint returns a status 200 code
     * @return void
     * @test
     */
    public function test_admin_areas_osm_endpoint_return_correct_response_with_correct_parameters()
    {
        $adminArea = DB::table('admin_areas')->inRandomOrder()->first();
        $osmType = match ($adminArea->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/admin-areas/osm/'.$osmType.'/'.$adminArea->osm_id);

        $response->assertStatus(200);
    }

    /**
     * Test if the endpoint returns a status 404 code
     * @return void
     * @test
     */
    public function test_admin_areas_osm_endpoint_return_404_response_with_wrong_osmtype()
    {
        $response = $this->get('/api/v1/features/admin-areas/osm/randomvalue/999999');

        $response->assertStatus(404);
    }

    /**
     * Test if the response has the correct structure
     * @return void
     * @test
     */
    public function test_admin_areas_osm_endpoint_return_correct_structure()
    {
        $adminArea = DB::table('admin_areas')->inRandomOrder()->first();
        $osmType = match ($adminArea->osm_type) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
        };
        $response = $this->get('/api/v1/features/admin-areas/osm/'.$osmType.'/'.$adminArea->osm_id);

        $response->assertJson(
            function (AssertableJson $json) {
                $json->has('type')
                    ->has('properties')
                    ->has('geometry')
                    ->has('properties.osm_type')
                    ->has('properties.osm_id')
                    ->has('properties.id')
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
}
