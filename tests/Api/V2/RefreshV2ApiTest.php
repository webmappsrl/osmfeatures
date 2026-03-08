<?php

namespace Tests\Api\V2;

use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Wm\WmPackage\Facades\OsmClient;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RefreshV2ApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('places')) {
            (new TestDBSeeder('Places'))->run();
        }
    }

    /** @test */
    public function refresh_with_invalid_id_format_returns_422()
    {
        $this->get('/api/v2/features/refresh/invalid')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid osmfeatures_id format. Expected R, W, or N followed by digits (e.g. R1234567).');
    }

    /** @test */
    public function refresh_with_nonexistent_id_fetches_from_osm_inserts_and_returns_200()
    {
        $id = 'N12580140344';
        OsmClient::shouldReceive('getPropertiesAndGeometry')
            ->once()
            ->with('node/12580140344')
            ->andReturn([
                ['name' => 'Test Node', '_updated_at' => '2024-01-15 12:00:00'],
                ['type' => 'Point', 'coordinates' => [10.5, 43.2]],
            ]);

        $this->get('/api/v2/features/refresh/' . $id)
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                ->where('type', 'Feature')
                ->has('properties')
                ->has('geometry')
                ->etc()
            );

        $this->assertDatabaseHas('places', [
            'osm_type' => 'N',
            'osm_id' => 12580140344,
        ]);
    }

    /** @test */
    public function refresh_with_valid_place_id_returns_200_and_feature()
    {
        $row = DB::table('places')->select('osm_type', 'osm_id')->first();
        if (! $row) {
            $this->markTestSkipped('No places in test database.');
        }

        $id = $row->osm_type . $row->osm_id;
        $osmType = $row->osm_type === 'N' ? 'node' : ($row->osm_type === 'W' ? 'way' : 'relation');
        $osmid = $osmType . '/' . $row->osm_id;

        $fakeProperties = ['name' => 'Test Place', '_updated_at' => now()->format('Y-m-d H:i:s')];
        $fakeGeometry = ['type' => 'Point', 'coordinates' => [10.0, 43.0]];

        OsmClient::shouldReceive('getPropertiesAndGeometry')
            ->once()
            ->with($osmid)
            ->andReturn([$fakeProperties, $fakeGeometry]);

        $this->get('/api/v2/features/refresh/' . $id)
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                ->where('type', 'Feature')
                ->has('properties')
                ->has('geometry')
                ->etc()
            );
    }
}
