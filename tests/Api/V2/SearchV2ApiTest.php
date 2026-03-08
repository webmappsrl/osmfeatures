<?php

namespace Tests\Api\V2;

use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SearchV2ApiTest extends TestCase
{
    /** @test */
    public function search_without_params_returns_422()
    {
        $this->get('/api/v2/features/search')->assertStatus(422);
    }

    /** @test */
    public function search_with_valid_bbox_returns_feature_collection()
    {
        $this->get('/api/v2/features/search?bbox=10.0,43.0,11.0,44.0')
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                ->where('type', 'FeatureCollection')
                ->has('features')
            );
    }

    /** @test */
    public function search_has_cache_control_header()
    {
        $this->get('/api/v2/features/search?bbox=10.0,43.0,11.0,44.0')
            ->assertHeader('Cache-Control');
    }

    /** @test */
    public function search_with_radius_returns_feature_collection()
    {
        $this->get('/api/v2/features/search?lat=43.5&lon=10.5&radius=5000')
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                ->where('type', 'FeatureCollection')
                ->has('features')
            );
    }

    /** @test */
    public function search_with_both_bbox_and_lat_lon_returns_422()
    {
        $this->get('/api/v2/features/search?bbox=10,43,11,44&lat=43.5&lon=10.5')
            ->assertStatus(422);
    }

    /** @test */
    public function search_with_invalid_model_returns_422()
    {
        $this->get('/api/v2/features/search?bbox=10,43,11,44&models=invalid-model')
            ->assertStatus(422);
    }

    /** @test */
    public function search_with_specific_model_returns_only_that_model()
    {
        $response = $this->get('/api/v2/features/search?bbox=10.0,43.0,11.0,44.0&models=poles')
            ->assertStatus(200);

        $features = $response->json('features');
        foreach ($features as $feature) {
            $this->assertEquals('poles', $feature['properties']['model']);
        }
    }
}
