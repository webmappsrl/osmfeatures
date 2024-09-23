<?php

namespace App\Models;

use App\Models\OsmfeaturesModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pole extends OsmfeaturesModel
{
    use HasFactory;

    protected $table = 'poles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'osm_id',
        'name',
        'osm_type',
        'admin_level',
        'tags',
        'geom',
        'updated_at',
    ];

    /**
     * Return the pole as a GeoJSON Feature.
     *
     * @return array
     */
    public function getGeojsonFeature()
    {
        $geom = $this->transformGeomToGeojson();
        $osmType = $this->getOsmType();
        $properties = $this->prepareProperties($osmType);

        //remove enrichments from properties
        unset($properties['enrichments']);

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return $geojsonFeature;
    }
}
