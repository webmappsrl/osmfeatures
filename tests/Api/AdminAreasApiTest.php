<?php

namespace Tests\Api;

use App\Models\AdminArea;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AdminAreasApiTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('admin_areas')) {
            Schema::create(
                'admin_areas',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->bigInteger('osm_id');
                    $table->string('osm_type');
                    $table->multiPolygon('geom');
                    $table->text('admin_level');
                    $table->timestamps();
                }
            );
            //create 200 admin areas
            for ($i = 0; $i < 200; $i++) {
                DB::table('admin_areas')->insert([
                    'name' => 'Admin Area ' . $i,
                    'osm_id' => $i,
                    'osm_type' => 'R',
                    'geom' => 'SRID=4326;MULTIPOLYGON(((-1 -1, 1 -1, 1 1, -1 1, -1 -1)))',
                    'admin_level' => '2' . $i,
                ]);
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
                            ->has('updated_at');
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
        $response = $this->get('/api/v1/features/admin-areas/list?&bbox=-180%2C-90%2C180%2C90');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('temp_admin_areas');

        parent::tearDown();
    }

    /**
     * Test if the http call with admin_level parameter returns the correct results
     * @test
     */
    public function list_admin_area_api_returns_correct_response()
    {
        $response = $this->get('/api/v1/features/admin-areas/list?admin_level=8');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }
}
