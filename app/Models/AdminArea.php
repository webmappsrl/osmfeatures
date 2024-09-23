<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmTagsProcessor;
use Illuminate\Support\Facades\DB;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminArea extends Pivot
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

    protected $table = 'admin_areas';

    protected $primaryKey = 'id';

    protected $fillable = [
        'osm_id',
        'osm_type',
        'updated_at',
        'name',
        'tags',
        'admin_level',
        'geom',
    ];

    protected $casts = [
        'admin_level ' => 'integer',
    ];

    /**
     * Return the admin area as a GeoJSON Feature.
     *
     * @return array
     */
    public function getGeojsonFeature()
    {
        $geom = $this->transformGeomToGeojson();
        $enrichment = $this->getEnrichment();
        $osmType = $this->getOsmType();
        $properties = $this->prepareProperties($osmType, $enrichment);

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
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

    /**
     * Return the OSM type from the database.
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
     * @param array|null $enrichment
     * @return array
     */
    private function prepareProperties($osmType, $enrichment)
    {
        $properties = $this->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        unset($properties['id']);

        $properties['osmfeatures_id'] = $this->getOsmFeaturesId();
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$this->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$this->osm_id.json";
        $properties['osm_tags'] = json_decode($this->tags, true);
        $properties['wikipedia'] = $this->getWikipediaUrl();
        $properties['wikidata'] = $this->getWikidataUrl();
        $properties['wikimedia_commons'] = $this->getWikimediaCommonsUrl();
        $properties['enrichments'] = $enrichment;

        return $properties;
    }
}
