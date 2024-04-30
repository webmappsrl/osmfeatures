<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestDBSeeder extends Seeder
{
    protected $class;

    //create a construct that must accept a class as parameter
    public function __construct(string $modelClass = null)
    {
        $this->class = $modelClass;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        switch ($this->class) {
            case 'AdminAreas':
                $this->createAdminAreasTableWithData();
                break;
            case 'Poles':
                $this->createPolesTableWithData();
                break;
            case 'HikingRoutes':
                $this->createHikingRoutesTableWithData();
                break;
            case 'Places':
                $this->createPlacesTableWithData();
                break;
            default:
                $this->createAdminAreasTableWithData();
                $this->createPolesTableWithData();
                $this->createHikingRoutesTableWithData();
                $this->createPlacesTableWithData();
        }
    }

    private function createAdminAreasTableWithData()
    {
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
        $osmTypes = ['N', 'R', 'W'];
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
                'osm_type' => $osmTypes[rand(0, 2)],
                'geom' => DB::raw("ST_GeomFromText('MULTIPOLYGON($polygon)')"),
                'admin_level' => rand(1, 11),
                'score' => rand(1, 4),
                'tags' => json_encode(['wikidata' => 'value', 'wikipedia' => 'value', 'wikimedia_commons' => 'value']),
                'updated_at' => Carbon::now(),
            ]);
        }
        $this->usingTestData = true;
    }

    private function createHikingRoutesTableWithData()
    {
        Schema::create(
            'hiking_routes',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->bigInteger('osm_id')->nullable();
                $table->string('osm_type')->nullable();
                $table->dateTime('updated_at_osm')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->text('cai_scale')->nullable();
                $table->integer('osm2cai_status')->nullable();
                $table->integer('score')->nullable();
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
            }
        );
        //create 200 hiking routes
        for ($i = 0; $i < 200; $i++) {
            $startLat = rand(3600, 4700) / 100; // Generate random latitude within bounds
            $startLon = rand(600, 1900) / 100; // Generate random longitude within bounds

            // Define a simple path by incrementing longitude and latitude
            $coords = [];
            for ($j = 0; $j < 5; $j++) {
                $coords[] = sprintf('%.2f %.2f', $startLon + 0.01 * $j, $startLat + 0.01 * $j);
            }
            $lineString = implode(', ', $coords);

            $geomText = "MULTILINESTRING(($lineString))";

            DB::table('hiking_routes')->insert([
                'name' => 'Hiking Route '.$i,
                'osm_id' => $i,
                'osm_type' => 'R',
                'geom' => DB::raw("ST_GeomFromText('$geomText', 4326)"),
                'updated_at' => now(),
                'updated_at_osm' => now(),
                'cai_scale' => 'scale',
                'osm2cai_status' => 1,
                'score' => rand(1, 7),
                'osmc_symbol' => 'symbol',
                'network' => 'lwn',
                'ref' => 'T8',
                'source' => 'source',
                'members' => json_encode(['member' => 'value']),
                'tags' => json_encode(['wikidata' => 'value', 'wikipedia' => 'value', 'wikimedia_commons' => 'value']),
                'survey_date' => '2021-01-01',
                'roundtrip' => 'yes',
                'symbol' => 'symbol',
                'symbol_it' => 'symbol_it',
                'ascent' => '1000',
                'descent' => '1000',
                'distance' => '10000',
                'duration_forward' => '01:00:00',
                'duration_backward' => '01:00:00',
                'from' => 'start',
                'to' => 'end',
                'rwn_name' => 'rwn_name',
                'ref_REI' => 'ref_REI',
                'maintenance' => 'maintenance',
                'maintenance_it' => 'maintenance_it',
                'operator' => 'operator',
                'state' => 'state',
                'source_ref' => 'source_ref',
                'note' => 'note',
                'note_it' => 'note_it',
                'old_ref' => 'old_ref',
                'note_project_page' => 'note_project_page',
                'website' => 'website',
                'wikimedia_commons' => 'wikimedia_commons',
                'description' => 'description',
                'description_it' => 'description_it',
            ]);
        }
    }

    private function createPlacesTableWithData()
    {
        Schema::create(
            'places',
            function (Blueprint $table) {
                $table->string('osm_type');
                $table->bigInteger('osm_id');
                $table->increments('id');
                $table->timestamp('updated_at');
                $table->string('name');
                $table->string('class');
                $table->string('subclass')->nullable();
                $table->point('geom');
                $table->jsonb('tags')->nullable();
                $table->integer('elevation')->nullable();
                $table->integer('score')->nullable();
            }
        );

        //create 200 places
        for ($i = 0; $i < 200; $i++) {
            // generate random point inside Italy bounding box
            $lat = rand(3600, 4700) / 100;
            $lon = rand(600, 1900) / 100;

            DB::table('places')->insert([
                'osm_type' => 'N',
                'osm_id' => $i,
                'updated_at' => now(),
                'name' => 'Place '.$i,
                'class' => 'class',
                'geom' => DB::raw("ST_GeomFromText('POINT($lon $lat)')"),
                'tags' => json_encode(['wikidata' => 'value', 'wikipedia' => 'value', 'wikimedia_commons' => 'value']),
                'elevation' => rand(50, 300),
                'score' => rand(1, 5),
            ]);
        }
    }

    private function createPolesTableWithData()
    {
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
            // generate random point inside Italy bounding box
            $lat = rand(3600, 4700) / 100;
            $lon = rand(600, 1900) / 100;

            DB::table('poles')->insert([
                'osm_type' => 'N',
                'osm_id' => $i,
                'updated_at' => now(),
                'name' => 'Pole '.$i,
                'tags' => json_encode(['wikidata' => 'value', 'wikipedia' => 'value', 'wikimedia_commons' => 'value']),
                'geom' => DB::raw("ST_GeomFromText('POINT($lon $lat)')"),
                'ref' => 'ref',
                'ele' => '1000',
                'destination' => 'destination',
                'support' => 'support',
                'score' => rand(1, 5),
            ]);
        }
    }
}
