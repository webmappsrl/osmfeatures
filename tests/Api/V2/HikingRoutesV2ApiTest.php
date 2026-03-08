<?php

namespace Tests\Api\V2;

use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class HikingRoutesV2ApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('hiking_routes')) {
            (new TestDBSeeder('HikingRoutes'))->run();
        }
    }

    /** @test */
    public function list_returns_200()
    {
        $this->get('/api/v2/features/hiking-routes/list')->assertStatus(200);
    }

    /** @test */
    public function list_returns_paginated_structure()
    {
        $this->get('/api/v2/features/hiking-routes/list')->assertJson(
            fn(AssertableJson $json) => $json
                ->has('data')
                ->has('current_page')
                ->has('total')
                ->has('per_page')
                ->has('data.0', fn($j) => $j->has('id')->has('updated_at')->missing('osm_type')->missing('osm_id'))
                ->etc()
        );
    }

    /** @test */
    public function list_has_cache_control_header()
    {
        $this->get('/api/v2/features/hiking-routes/list')
            ->assertHeader('Cache-Control');
    }

    /** @test */
    public function list_filtered_by_updated_at()
    {
        $this->get('/api/v2/features/hiking-routes/list?updated_at=2000-01-01T00:00:00Z')
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has('data')->etc());
    }

    /** @test */
    public function list_with_invalid_bbox_returns_400()
    {
        $this->get('/api/v2/features/hiking-routes/list?bbox=invalid')
            ->assertStatus(400);
    }

    /** @test */
    public function show_returns_404_for_unknown_id()
    {
        $this->get('/api/v2/features/hiking-routes/R999999999')->assertStatus(404);
    }

    /** @test */
    public function show_returns_geojson_structure()
    {
        if (! Schema::hasTable('hiking_routes') || \DB::table('hiking_routes')->count() === 0) {
            $this->markTestSkipped('Nessuna hiking route nel DB di test.');
        }

        $hr = \DB::table('hiking_routes')->select('osm_type', 'osm_id')->first();
        $id = $hr->osm_type . $hr->osm_id;

        $this->get("/api/v2/features/hiking-routes/{$id}")
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('type', 'Feature')
                ->has('properties')
                ->has('geometry')
            )
            ->assertHeader('ETag')
            ->assertHeader('Cache-Control');
    }
}
