<?php

namespace Tests\Feature;

use App\Models\AdminArea;
use Tests\TestCase;
use App\Traits\OsmFeaturesIdProcessor;
use Database\Seeders\TestDBSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OsmfeaturesIdProcessorTest extends TestCase
{
    use RefreshDatabase, OsmFeaturesIdProcessor;

    protected $usingTestData = false;


    public function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('admin_areas')) {
            $seeder = new TestDBSeeder();
            $seeder->run();
            $this->usingTestData = true;
        }
    }
    /**
     * test the getOsmFeaturesId function
     * @test
     */
    public function test_trait_exists()
    {
        $this->assertTrue(trait_exists(OsmFeaturesIdProcessor::class));
    }

    /**
     * test the getOsmFeaturesId function
     * @test
     */
    public function test_getOsmFeaturesId_function()
    {

        $nodeModel = AdminArea::where('osm_type', 'N')->first();
        $wayModel = AdminArea::where('osm_type', 'W')->first();
        $relationModel = AdminArea::where('osm_type', 'R')->first();


        //first character of node model osmfeatures id should be N
        $this->assertSame('N', substr($nodeModel->getOsmFeaturesId(), 0, 1));
        //first character of way model osmfeatures id should be W
        $this->assertSame('W', substr($wayModel->getOsmFeaturesId(), 0, 1));
        //first character of relation model osmfeatures id should be R
        $this->assertSame('R', substr($relationModel->getOsmFeaturesId(), 0, 1));

        $this->assertSame($nodeModel->osm_type . $nodeModel->osm_id, $nodeModel->getOsmFeaturesId());
        $this->assertSame($wayModel->osm_type . $wayModel->osm_id, $wayModel->getOsmFeaturesId());
        $this->assertSame($relationModel->osm_type . $relationModel->osm_id, $relationModel->getOsmFeaturesId());
    }

    /**
     * test the getOsmfeaturesByOsmfeaturesId function
     * @test
     */
    public function test_getOsmfeaturesByOsmfeaturesId_function()
    {
        $model = AdminArea::first();
        $osmfeaturesId = $model->getOsmFeaturesId();

        $modelRetrieved = AdminArea::getOsmfeaturesByOsmfeaturesId($osmfeaturesId);

        //assert that the model is not null
        $this->assertNotNull($modelRetrieved);

        //assert that the model retrieved is the same as the model
        $this->assertEquals($model->id, $modelRetrieved->id);
        $this->assertTrue($modelRetrieved->is($model));
        $this->assertTrue($modelRetrieved->getOsmFeaturesId() == $osmfeaturesId);
    }
}
