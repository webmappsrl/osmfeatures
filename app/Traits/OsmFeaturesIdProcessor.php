<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OsmFeaturesIdProcessor
{
    /**
     * Get the osmfeatures id by combining osm_type and osm_id
     * 
     * @return string
     */
    public function getOsmFeaturesId(): string
    {
        return $this->osm_type . $this->osm_id;
    }

    /**
     * Get an instance of the model based on the osmfeatures ID
     * 
     * @param string $osmFeaturesId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function getOsmfeaturesByOsmfeaturesID(string $osmFeaturesId): ?self
    {
        // Split osmFeaturesId into osm_type and osm_id
        $osmType = substr($osmFeaturesId, 0, 1);
        $osmId = substr($osmFeaturesId, 1);

        // Create a query builder
        $query = static::query();

        // Apply conditions for osm_type and osm_id
        $query->where('osm_type', $osmType)->where('osm_id', $osmId);

        // Get the first matching instance
        return $query->first();
    }
}
