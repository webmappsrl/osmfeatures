<?php

namespace Tests\Feature\Api;

use App\Models\Pole;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PolesApiTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        // 1	osm_type	bpchar(1)	NO	NULL	NULL		NULL
        // 2	osm_id	int8	NO	NULL	NULL		NULL
        // 3	id	int4	NO	NULL	"nextval('poles_tmp_id_seq'::regclass)"		NULL
        // 4	updated_at	text	YES	NULL	NULL		NULL
        // 5	name	text	YES	NULL	NULL		NULL
        // 6	tags	jsonb	YES	NULL	NULL		NULL
        // 7	geom	geometry(Point,3857)	YES	NULL	NULL		NULL
        // 8	ref	text	YES	NULL	NULL		NULL
        // 9	ele	text	YES	NULL	NULL		NULL
        // 10	destination	text	YES	NULL	NULL		NULL
        // 11	support	text	YES	NULL	NULL		NULL

        if (! Schema::hasTable('poles')) {
            Schema::create('poles', function (Blueprint $table) {
                $table->string('osm_type');
                $table->bigInteger('osm_id');
                $table->increments('id');
                $table->timestamp('updated_at');
                $table->string('name');
                $table->jsonb('tags')->nullable();
                $table->point('geom');
                $table->string('ref')->nullable();
                $table->string('ele')->nullable();
                $table->string('destination')->nullable();
                $table->string('support')->nullable();
                $table->integer('score')->nullable();
            });

            //create 200 poles
            for ($i = 0; $i < 200; $i++) {
                DB::table('poles')->insert([
                    'osm_type' => 'N',
                    'osm_id' => $i,
                    'updated_at' => now(),
                    'name' => 'Pole '.$i,
                    'tags' => json_encode(['tag' => 'value']),
                    'geom' => DB::raw('ST_GeomFromText(\'POINT(0 0)\')'),
                    'ref' => 'ref',
                    'ele' => 'ele',
                    'destination' => 'destination',
                    'support' => 'support',
                    'score' => rand(1, 5),
                ]);
            }
        }
    }

    /**
     * Return code 200
     * @test
     */
    public function list_poles_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/poles/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the admin_area list api returns results
     * @test
     */
    public function list_poles_api_returns_poles()
    {
        $response = $this->get('/api/v1/features/poles/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the json has the correct structure
     * @test
     */
    public function list_poles_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/poles/list');

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
    public function list_poles_api_returns_correct_results_with_page()
    {
        $response = $this->get('/api/v1/features/poles/list?page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Text if the http call with bbox parameter returns the correct results
     * @test
     */
    public function list_poles_api_returns_correct_number_of_results_with_bbox()
    {
        $response = $this->get('/api/v1/features/poles/list?&bbox=-180%2C-90%2C180%2C90');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Test if the http call with score parameter returns the correct results
     * @test
     */
    public function list_poles_api_returns_correct_response_with_score()
    {
        $response = $this->get('/api/v1/features/poles/list?score=1');

        $response->assertStatus(200);
        $this->assertNotEquals(0, count($response->json()['data']));
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('temp_poles');

        parent::tearDown();
    }
}
