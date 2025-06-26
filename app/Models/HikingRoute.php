<?php

namespace App\Models;

use App\Models\OsmfeaturesModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HikingRoute extends OsmfeaturesModel
{
    use HasFactory;

    protected $table = 'hiking_routes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'updated_at',
        'admin_areas',
    ];

    /**
     * Returns the GeoJSON feature for the Hiking Route.
     *
     * @return array
     */
    public function getGeojsonFeature(array $props = []): array
    {

        // Get the geometry in GeoJSON format
        $geom = $this->transformGeomToGeojson();

        // Get the DEM enrichment
        $demEnrichment = $this->demEnrichment ? json_decode($this->demEnrichment->data, true) : null;

        // Get the admin areas enrichment
        $adminAreas = $this->adminAreasEnrichment ? json_decode($this->adminAreasEnrichment->data, true) : null;

        // Get the OSM type
        $osmType = $this->getOsmType($this->osm_type);

        // Prepare the properties
        $properties = $this->prepareProperties($osmType);
        $properties['admin_areas'] = $adminAreas;
        $properties['dem_enrichment'] = $demEnrichment ? $demEnrichment['properties'] : null;

        if (!empty($props)) {
            $properties = array_intersect_key($properties, array_flip($props));
        }


        // Return the GeoJSON feature
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return $geojsonFeature;
    }


    /**
     * Prepare the properties for the GeoJSON feature.
     *
     * @param string $osmType
     * @return array
     */
    protected function prepareProperties($osmType): array
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

        $properties = $this->normalizeProperties($properties);

        return $properties;
    }
}
