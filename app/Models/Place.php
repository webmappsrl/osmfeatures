<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmTagsProcessor;
use Illuminate\Support\Facades\DB;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

    protected $table = 'places';

    protected $fillable = ['osm_id', 'name', 'class', 'subclass', 'geom', 'updated_at'];

    protected $primaryKey = 'id';

    /**
     * Get the GeoJSON representation of the Place.
     *
     * @return array
     */
    public function getGeojsonFeature()
    {
        $geom = $this->transformGeomToGeojson();
        $osmType = $this->getOsmType();
        $properties = $this->prepareProperties($osmType);
        $enrichment = $this->getEnrichment();

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return $geojsonFeature;
    }

    /**
     * Transform the geometry from the database to a GeoJSON representation.
     *
     * @return string
     */
    private function transformGeomToGeojson()
    {
        return DB::select('SELECT ST_AsGeoJSON(ST_Transform(?, 4326)) AS geojson', [$this->geom])[0]->geojson;
    }

    /**
     * Get the OSM type from the database.
     *
     * @return string|null
     */
    private function getOsmType()
    {
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
        unset($properties['enrichment']);

        $properties['osmfeatures_id'] = $this->getOsmFeaturesId();
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$this->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$this->osm_id.json";
        $properties['osm_tags'] = json_decode($this->tags, true);
        $properties['wikidata'] = $this->getWikidataUrl();
        $properties['wikipedia'] = $this->getWikipediaUrl();
        $properties['wikimedia_commons'] = $this->getWikimediaCommonsUrl();
        $properties['enrichments'] = $this->getEnrichment();

        return $properties;
    }

    /**
     * Get the enrichment data from the database.
     *
     * @return array|null
     */
    private function getEnrichment()
    {
        if ($this->enrichment) {
            $enrichment = json_decode($this->enrichment, true);
            $enrichment['data'] = json_decode($enrichment['data'], true);
            return $enrichment;
        }
        return null;
    }
}
