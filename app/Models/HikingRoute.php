<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmTagsProcessor;
use Illuminate\Support\Facades\DB;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HikingRoute extends Model
{
    use HasFactory;
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;

    protected $table = 'hiking_routes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'updated_at',
        'has_invalid_geometry',
        'admin_areas',
    ];

    /**
     * Returns the GeoJSON feature for the Hiking Route.
     *
     * @return array
     */
    public function getGeojsonFeature(): array
    {

        // Get the geometry in GeoJSON format
        $geom = DB::select('SELECT ST_AsGeoJSON(ST_Transform(?, 4326)) AS geojson', [$this->geom])[0]->geojson;

        // Get the DEM enrichment
        $demEnrichment = $this->demEnrichment ? json_decode($this->demEnrichment->data, true) : null;

        // Get the admin areas enrichment
        $adminAreas = $this->adminAreasEnrichment ? json_decode($this->adminAreasEnrichment->data, true) : null;

        // Get the OSM type
        $osmType = $this->getOsmType($this->osm_type);

        // Prepare the properties
        $properties = $this->prepareProperties($osmType, $demEnrichment, $adminAreas);

        // Return the GeoJSON feature
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return $geojsonFeature;
    }

    /**
     * Returns the OSM type from the given osm_type.
     *
     * @param string $osmType
     * @return string
     */
    private function getOsmType($osmType): string
    {
        switch ($osmType) {
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
     * @param array|null $demEnrichment
     * @param array|null $adminAreas
     * @return array
     */
    private function prepareProperties($osmType, $demEnrichment, $adminAreas): array
    {
        $properties = $this->toArray();
        unset($properties['geom'], $properties['tags'], $properties['id'], $properties['admin_areas_enrichment']);
        $properties['osmfeatures_id'] = $this->id;
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$this->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$this->osm_id.json";
        $properties['osm_tags'] = json_decode($this->tags, true);
        $properties['members'] = json_decode($this->members, true);
        $properties['wikidata'] = $this->getWikidataUrl();
        $properties['wikipedia'] = $this->getWikipediaUrl();
        $properties['wikimedia_commons'] = $this->getWikimediaCommonsUrl();
        $properties['admin_areas'] = $adminAreas;
        $properties['dem_enrichment'] = $demEnrichment ? $demEnrichment['properties'] : null;

        return $properties;
    }
}
