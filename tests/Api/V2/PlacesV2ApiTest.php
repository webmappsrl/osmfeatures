<?php

namespace Tests\Api\V2;

use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PlacesV2ApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('places')) {
            (new TestDBSeeder('Places'))->run();
        }
    }

    /** @test */
    public function list_returns_200()
    {
        $this->get('/api/v2/features/places/list')->assertStatus(200);
    }

    /** @test */
    public function list_returns_paginated_structure()
    {
        $this->get('/api/v2/features/places/list')->assertJson(
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
        $this->get('/api/v2/features/places/list')->assertHeader('Cache-Control');
    }

    /** @test */
    public function show_returns_404_for_unknown_id()
    {
        $this->get('/api/v2/features/places/N999999999')->assertStatus(404);
    }

    /** @test */
    public function show_returns_geojson_structure()
    {
        if (! Schema::hasTable('places') || \DB::table('places')->count() === 0) {
            $this->markTestSkipped('Nessun place nel DB di test.');
        }

        $place = \DB::table('places')->select('osm_type', 'osm_id')->first();
        $id = $place->osm_type . $place->osm_id;

        $this->get("/api/v2/features/places/{$id}")
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                ->where('type', 'Feature')
                ->has('properties')
                ->has('geometry')
            )
            ->assertHeader('ETag')
            ->assertHeader('Cache-Control');
    }

    /** @test */
    public function get_places_by_distance_with_invalid_lon_returns_400()
    {
        $this->get('/api/v2/features/places/abc/46.0/1000')->assertStatus(400);
    }
}
