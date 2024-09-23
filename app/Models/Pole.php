<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmTagsProcessor;
use Illuminate\Support\Facades\DB;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pole extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

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

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return $geojsonFeature;
    }

    /**
     * Transform the geometry to GeoJSON.
     *
     * @return string
     */
    private function transformGeomToGeojson()
    {
        return DB::select('SELECT ST_AsGeoJSON(ST_Transform(?, 4326)) AS geojson', [$this->geom])[0]->geojson;
    }

    /**
     * Return the OSM type (relation, way, node).
     *
     * @return string
     */
    private function getOsmType()
    {
        // The OSM type is determined by the first character of the osm_type field.
        switch ($this->osm_type) {
            case 'R':
                return 'relation';
            case 'W':
                return 'way';
            case 'N':
                return 'node';
            default:
                return null;
        }
    }

    /**
     * Prepare the properties for the GeoJSON feature.
     *
     * @param string $osmType
     * @return array
     */
    private function prepareProperties($osmType)
    {
        $properties = $this->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        unset($properties['id']);

        // Add additional properties to the array.
        $properties['osmfeatures_id'] = $this->getOsmFeaturesId();
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$this->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$this->osm_id.json";
        $properties['osm_tags'] = json_decode($this->tags, true);
        $properties['wikidata'] = $this->getWikidataUrl();
        $properties['wikipedia'] = $this->getWikipediaUrl();
        $properties['wikimedia_commons'] = $this->getWikimediaCommonsUrl();

        return $properties;
    }
}
