<?php

namespace Tests\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class HikingRoutesApiTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        //         1	osm_type	bpchar(1)	NO	NULL	NULL		NULL
        // 2	osm_id	int8	NO	NULL	NULL		NULL
        // 3	id	int4	NO	NULL	"nextval('hiking_routes_tmp_id_seq'::regclass)"		NULL
        // 4	updated_at_osm	text	YES	NULL	NULL		NULL
        // 5	updated_at	text	YES	NULL	NULL		NULL
        // 6	name	text	YES	NULL	NULL		NULL
        // 7	cai_scale	text	YES	NULL	NULL		NULL
        // 8	osm2cai_status	int4	YES	NULL	NULL		NULL
        // 9	osmc_symbol	text	YES	NULL	NULL		NULL
        // 10	network	text	YES	NULL	NULL		NULL
        // 11	survey_date	text	YES	NULL	NULL		NULL
        // 12	roundtrip	text	YES	NULL	NULL		NULL
        // 13	symbol	text	YES	NULL	NULL		NULL
        // 14	symbol_it	text	YES	NULL	NULL		NULL
        // 15	ascent	text	YES	NULL	NULL		NULL
        // 16	descent	text	YES	NULL	NULL		NULL
        // 17	distance	text	YES	NULL	NULL		NULL
        // 18	duration_forward	text	YES	NULL	NULL		NULL
        // 19	duration_backward	text	YES	NULL	NULL		NULL
        // 20	from	text	YES	NULL	NULL		NULL
        // 21	to	text	YES	NULL	NULL		NULL
        // 22	rwn_name	text	YES	NULL	NULL		NULL
        // 23	ref_REI	text	YES	NULL	NULL		NULL
        // 24	maintenance	text	YES	NULL	NULL		NULL
        // 25	maintenance_it	text	YES	NULL	NULL		NULL
        // 26	operator	text	YES	NULL	NULL		NULL
        // 27	state	text	YES	NULL	NULL		NULL
        // 28	ref	text	YES	NULL	NULL		NULL
        // 29	source	text	YES	NULL	NULL		NULL
        // 30	source_ref	text	YES	NULL	NULL		NULL
        // 31	note	text	YES	NULL	NULL		NULL
        // 32	note_it	text	YES	NULL	NULL		NULL
        // 33	old_ref	text	YES	NULL	NULL		NULL
        // 34	note_project_page	text	YES	NULL	NULL		NULL
        // 35	website	text	YES	NULL	NULL		NULL
        // 36	wikimedia_commons	text	YES	NULL	NULL		NULL
        // 37	description	text	YES	NULL	NULL		NULL
        // 38	description_it	text	YES	NULL	NULL		NULL
        // 39	tags	jsonb	YES	NULL	NULL		NULL
        // 40	geom	geometry(MultiLineString,4326)	YES	NULL	NULL		NULL
        // 41	members	jsonb	YES	NULL	NULL		NULL

        if (! Schema::hasTable('hiking_routes')) {
            Schema::create(
                'hiking_routes',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name')->nullable();
                    $table->bigInteger('osm_id')->nullable();
                    $table->string('osm_type')->nullable();
                    $table->dateTime('updated_at')->nullable();
                    $table->text('cai_scale')->nullable();
                    $table->integer('osm2cai_status')->nullable();
                    $table->text('osmc_symbol')->nullable();
                    $table->text('network')->nullable();
                    $table->text('survey_date')->nullable();
                    $table->text('roundtrip')->nullable();
                    $table->text('symbol')->nullable();
                    $table->text('symbol_it')->nullable();
                    $table->text('ascent')->nullable();
                    $table->text('descent')->nullable();
                    $table->text('distance')->nullable();
                    $table->text('duration_forward')->nullable();
                    $table->text('duration_backward')->nullable();
                    $table->text('from')->nullable();
                    $table->text('to')->nullable();
                    $table->text('rwn_name')->nullable();
                    $table->text('ref_REI')->nullable();
                    $table->text('maintenance')->nullable();
                    $table->text('maintenance_it')->nullable();
                    $table->text('operator')->nullable();
                    $table->text('state')->nullable();
                    $table->text('ref')->nullable();
                    $table->text('source')->nullable();
                    $table->text('source_ref')->nullable();
                    $table->text('note')->nullable();
                    $table->text('note_it')->nullable();
                    $table->text('old_ref')->nullable();
                    $table->text('note_project_page')->nullable();
                    $table->text('website')->nullable();
                    $table->text('wikimedia_commons')->nullable();
                    $table->text('description')->nullable();
                    $table->text('description_it')->nullable();
                    $table->jsonb('tags')->nullable();
                    $table->multiLineString('geom')->nullable();
                    $table->jsonb('members')->nullable();
                    $table->timestamps();
                }
            );
            //create 200 hiking routes
            for ($i = 0; $i < 200; $i++) {
                DB::table('hiking_routes')->insert([
                    'name' => 'Hiking Route '.$i,
                    'osm_id' => $i,
                    'osm_type' => 'R',
                    'geom' => 'SRID=4326;MULTILINESTRING((0 0, 1 1, 2 2))',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Return code 200
     * @test
     */
    public function list_hiking_routes_api_returns_code_200()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list');

        //ensure that the response has a 200 status code
        $response->assertStatus(200);
    }

    /**
     * Test if the admin_area list api returns results
     * @test
     */
    public function list_hiking_routes_api_returns_hiking_routes()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list');

        //ensure the response return some result and not an empty json
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test if the json has the correct structure
     * @test
     */
    public function list_hiking_routes_api_returns_correct_structure()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list');

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
    public function list_hiking_routes_api_returns_correct_results_with_page()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list?page=1');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    /**
     * Text if the http call with bbox parameter returns the correct results
     * @test
     */
    public function list_hiking_routes_api_returns_correct_number_of_results_with_bbox()
    {
        $response = $this->get('/api/v1/features/hiking-routes/list?&bbox=-180%2C-90%2C180%2C90');

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'data');
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('temp_hiking_routes');

        parent::tearDown();
    }
}
