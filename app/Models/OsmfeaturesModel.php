<?php

namespace App\Models;

use App\Traits\Enrichable;
use App\Traits\OsmTagsProcessor;
use Illuminate\Support\Facades\DB;
use App\Traits\OsmFeaturesIdProcessor;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OsmfeaturesModel extends Model
{
    use OsmTagsProcessor;
    use OsmFeaturesIdProcessor;
    use Enrichable;


    /**
     * Return the GeoJSON representation.
     *
     * @param array $props
     * @return array
     */
    public function getGeojsonFeature(array $props = []): array
    {
        $geom = $this->transformGeomToGeojson();
        $osmType = $this->getOsmType();
        $properties = $this->prepareProperties($osmType);

        if (!empty($props)) {
            $properties = array_intersect_key($properties, array_flip($props));
        }

        return [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];
    }


    /**
     * Transform the geometry to GeoJSON.
     *
     * @return string
     */
    protected function transformGeomToGeojson()
    {
        return DB::select('SELECT ST_AsGeoJSON(ST_Transform(?, 4326)) AS geojson', [$this->geom])[0]->geojson;
    }

    /**
     * Get the OSM type from the database.
     *
     * @return string|null
     */
    protected function getOsmType()
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
    protected function prepareProperties($osmType)
    {
        $properties = $this->toArray();
        unset($properties['geom'], $properties['tags'], $properties['id']);

        $properties['osmfeatures_id'] = $this->getOsmFeaturesId();
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$this->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$this->osm_id.json";
        $properties['osm_tags'] = json_decode($this->tags, true);
        $properties['wikidata'] = $this->getWikidataUrl();
        $properties['wikipedia'] = $this->getWikipediaUrl();
        $properties['wikimedia_commons'] = $this->getWikimediaCommonsUrl();
        $properties['enrichments'] = $this->getEnrichment();

        // Normalize numeric fields
        $properties = $this->normalizeProperties($properties);

        return $properties;
    }

    /**
     * Get the enrichment data from the database.
     *
     * @return array|null
     */
    protected function getEnrichment()
    {
        if ($this->enrichment) {
            $enrichment = json_decode($this->enrichment, true);
            $enrichment['data'] = json_decode($enrichment['data'], true);
            return $enrichment;
        }
        return null;
    }

    /**
     * Convert duration string to hours as float.
     *
     * @param mixed $duration
     * @return float
     */
    public function convertDurationToMinutes($duration): float
    {
        if (empty($duration)) {
            return 0.0;
        }

        // If it's already a number, assume it's in seconds and convert to minutes.
        if (is_numeric($duration)) {
            return (float) $duration / 60;
        }

        // If it's a string, try to parse it.
        if (is_string($duration)) {
            $parts = explode(':', $duration);
            $minutes = 0;
            if (count($parts) === 3) { // HH:MM:SS
                $minutes = (int) $parts[0] * 60 + (int) $parts[1] + ((int) $parts[2] / 60);
            } elseif (count($parts) === 2) { // HH:MM
                $minutes = (int) $parts[0] * 60 + (int) $parts[1];
            }

            return (float) $minutes;
        }

        // Fallback for unknown types
        return 0.0;
    }

    /**
     * Normalize properties data types.
     *
     * @param array $properties
     * @return array
     */
    protected function normalizeProperties(array $properties): array
    {
        // Numeric fields that should be converted to numbers
        $numericFields = ['ascent', 'descent', 'distance'];

        // Duration fields that should be converted to minutes
        $durationFields = ['duration_forward', 'duration_backward'];

        foreach ($numericFields as $field) {
            if (isset($properties[$field]) && !empty($properties[$field])) {
                $properties[$field] = is_numeric($properties[$field])
                    ? (float) $properties[$field]
                    : 0.0;
            }
        }

        foreach ($durationFields as $field) {
            if (isset($properties[$field]) && !empty($properties[$field])) {
                $properties[$field] = $this->convertDurationToMinutes($properties[$field]);
            }
        }

        return $properties;
    }
}
