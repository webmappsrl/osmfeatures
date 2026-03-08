<?php

namespace Tests\Api\V2;

use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AdminAreasV2ApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('admin_areas')) {
            (new TestDBSeeder('AdminAreas'))->run();
        }
    }

    /** @test */
    public function list_returns_200()
    {
        $this->get('/api/v2/features/admin-areas/list')->assertStatus(200);
    }

    /** @test */
    public function list_returns_paginated_structure()
    {
        $this->get('/api/v2/features/admin-areas/list')->assertJson(
            fn(AssertableJson $json) => $json
                ->has('data')
                ->has('current_page')
                ->has('total')
                ->has('per_page')
                ->etc()
        );
    }

    /** @test */
    public function list_has_cache_control_header()
    {
        $this->get('/api/v2/features/admin-areas/list')->assertHeader('Cache-Control');
    }

    /** @test */
    public function show_returns_404_for_unknown_id()
    {
        $this->get('/api/v2/features/admin-areas/R999999999')->assertStatus(404);
    }

    /** @test */
    public function show_returns_geojson_structure()
    {
        if (! Schema::hasTable('admin_areas') || \DB::table('admin_areas')->count() === 0) {
            $this->markTestSkipped('Nessuna admin area nel DB di test.');
        }

        $area = \DB::table('admin_areas')->select('osm_type', 'osm_id')->first();
        $id = $area->osm_type . $area->osm_id;

        $this->get("/api/v2/features/admin-areas/{$id}")
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('type', 'Feature')
                ->has('properties')
                ->has('geometry')
            )
            ->assertHeader('ETag')
            ->assertHeader('Cache-Control');
    }

    /** @test */
    public function intersecting_geojson_returns_feature_collection()
    {
        $payload = [
            'geojson' => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [10.0, 44.0],
                ],
                'properties' => [],
            ],
        ];

        $this->postJson('/api/v2/features/admin-areas/geojson', $payload)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('type', 'FeatureCollection')
                ->has('features')
            );
    }

    /** @test */
    public function intersecting_geojson_missing_geometry_returns_422()
    {
        $this->postJson('/api/v2/features/admin-areas/geojson', ['geojson' => ['type' => 'Feature']])
            ->assertStatus(422);
    }
}
