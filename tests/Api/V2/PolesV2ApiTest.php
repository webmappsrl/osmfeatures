<?php

namespace Tests\Api\V2;

use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PolesV2ApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('poles')) {
            (new TestDBSeeder('Poles'))->run();
        }
    }

    /** @test */
    public function list_returns_200()
    {
        $this->get('/api/v2/features/poles/list')->assertStatus(200);
    }

    /** @test */
    public function list_returns_paginated_structure()
    {
        $this->get('/api/v2/features/poles/list')->assertJson(
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
        $this->get('/api/v2/features/poles/list')->assertHeader('Cache-Control');
    }

    /** @test */
    public function show_returns_404_for_unknown_id()
    {
        $this->get('/api/v2/features/poles/N999999999')->assertStatus(404);
    }

    /** @test */
    public function show_returns_geojson_structure()
    {
        if (! Schema::hasTable('poles') || \DB::table('poles')->count() === 0) {
            $this->markTestSkipped('Nessun pole nel DB di test.');
        }

        $pole = \DB::table('poles')->select('osm_type', 'osm_id')->first();
        $id = $pole->osm_type . $pole->osm_id;

        $this->get("/api/v2/features/poles/{$id}")
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
