<?php

namespace App\Services\V2;

/**
 * Replica la logica degli script Lua (places, poles, hiking_routes) per un singolo elemento.
 * Usato dal refresh via OSM API per allineare update/insert al comportamento del sync PBF.
 */
class OsmLuaLogic
{
    /**
     * Mapping OSM tag -> class/subclass come in places.lua (key -> [value => subclass], class).
     *
     * @var array<int, array{key: string, class: string, values: array<string, string>}>
     */
    private const PLACE_MAPPINGS = [
        ['key' => 'landuse', 'class' => 'landuse', 'values' => ['cemetery' => 'cemetery']],
        ['key' => 'water', 'class' => 'water', 'values' => ['lake' => 'lake', 'pond' => 'pond', 'lagoon' => 'lagoon', 'basin' => 'basin', 'reservoir' => 'reservoir']],
        ['key' => 'waterway', 'class' => 'waterway', 'values' => ['waterfall' => 'waterfall']],
        ['key' => 'building', 'class' => 'building', 'values' => ['railway_station' => 'station', 'castle' => 'castle', 'monastery' => 'monastery', 'ruins' => 'ruins', 'tower' => 'tower', 'museum' => 'museum', 'church' => 'church', 'chapel' => 'chapel']],
        ['key' => 'man_made', 'class' => 'man_made', 'values' => ['tower' => 'tower', 'watermill' => 'watermill']],
        ['key' => 'aerialway_station', 'class' => 'aerialway', 'values' => ['station' => 'station']],
        ['key' => 'place', 'class' => 'place', 'values' => ['city' => 'city', 'town' => 'town', 'suburb' => 'suburb', 'borough' => 'suburb', 'quarter' => 'suburb', 'neighbourhood' => 'suburb', 'allotments' => 'suburb', 'village' => 'village', 'square' => 'square', 'island' => 'island', 'islet' => 'islet', 'hamlet' => 'hamlet', 'isolated_dwelling' => 'isolated_dwelling', 'farm' => 'isolated_dwelling', 'locality' => 'locality']],
        ['key' => 'natural', 'class' => 'natural', 'values' => ['peak' => 'peak', 'saddle' => 'saddle', 'cape' => 'cape', 'beach' => 'beach', 'spring' => 'spring', 'glacier' => 'glacier', 'cave_entrance' => 'cave_entrance', 'wood' => 'wood', 'tree' => 'tree', 'water' => 'water', 'hot_spring' => 'hot_spring', 'sinkhole' => 'sinkhole', 'cliff' => 'cliff', 'rock' => 'rock', 'volcano' => 'volcano']],
        ['key' => 'tourism', 'class' => 'tourism', 'values' => ['alpine_hut' => 'alpine_hut', 'wilderness_hut' => 'wilderness_hut', 'aquarium' => 'aquarium', 'camp_site' => 'camp_site', 'caravan_site' => 'caravan_site', 'picnic_site' => 'picnic_site', 'hostel' => 'hostel', 'museum' => 'museum', 'zoo' => 'zoo', 'theme_park' => 'theme_park', 'artwork' => 'artwork']],
        ['key' => 'historic', 'class' => 'historic', 'values' => ['wayside_shrine' => 'wayside_shrine', 'wayside_cross' => 'wayside_cross', 'monastery' => 'monastery', 'archaeological_site' => 'archaeological_site', 'castle' => 'castle', 'farm' => 'farm', 'fort' => 'fort', 'manor' => 'manor', 'tower' => 'tower', 'city_gate' => 'city_gate', 'church' => 'church']],
        ['key' => 'amenity', 'class' => 'amenity', 'values' => ['place_of_worship' => 'place_of_worship', 'cemetery' => 'grave_yard', 'bus_station' => 'bus_station', 'parking' => 'parking', 'drinking_water' => 'drinking_water', 'hospital' => 'hospital', 'theatre' => 'theatre', 'university' => 'university', 'public_building' => 'public_building', 'planetarium' => 'planetarium', 'rock_shelter' => 'rock_shelter', 'lavoir' => 'lavoir', 'social_facility' => 'social_facility', 'community_centre' => 'community_centre', 'neviera' => 'neviera', 'watering_place' => 'watering_place', 'shelter' => 'shelter', 'public_bath' => 'public_bath', 'water_point' => 'water_point', 'fountain' => 'fountain']],
    ];

    /**
     * Score place come in places.lua (1-6).
     */
    public static function placeScore(array $tags): int
    {
        if (! empty($tags['name'])) {
            return 1;
        }
        if (! empty($tags['elem'])) {
            return 2;
        }
        if (! empty($tags['wikidata'])) {
            return 3;
        }
        if (! empty($tags['wikimedia_commons'])) {
            return 4;
        }
        if (! empty($tags['wikipedia'])) {
            return 5;
        }
        if (! empty($tags['contact:website']) || ! empty($tags['source']) || ! empty($tags['website'])) {
            return 6;
        }
        return 0;
    }

    /**
     * Class e subclass place come in places.lua (primo mapping che matcha).
     *
     * @return array{0: string, 1: string} [class, subclass]
     */
    public static function placeClassSubclass(array $tags): array
    {
        foreach (self::PLACE_MAPPINGS as $m) {
            $value = $tags[$m['key']] ?? null;
            if ($value !== null && isset($m['values'][$value])) {
                return [$m['class'], $m['values'][$value]];
            }
        }
        return ['place', 'other'];
    }

    /**
     * Score pole come in poles.lua (somma name, wikidata, wikipedia, wikimedia_commons, ele).
     */
    public static function poleScore(array $tags): int
    {
        $s = 0;
        if (! empty($tags['name'])) {
            $s++;
        }
        if (! empty($tags['wikidata'])) {
            $s++;
        }
        if (! empty($tags['wikipedia'])) {
            $s++;
        }
        if (! empty($tags['wikimedia_commons'])) {
            $s++;
        }
        if (! empty($tags['ele'])) {
            $s++;
        }
        return $s;
    }

    /**
     * osm2cai_status come in hiking_routes.lua (0, 1, 2, 3).
     */
    public static function hikingRouteOsm2caiStatus(array $tags): int
    {
        $caiScalePresent = isset($tags['cai_scale']);
        $surveyCaiPresent = isset($tags['source']) && str_contains((string) $tags['source'], 'survey:CAI');
        if ($caiScalePresent && $surveyCaiPresent) {
            return 3;
        }
        if ($caiScalePresent) {
            return 1;
        }
        if ($surveyCaiPresent) {
            return 2;
        }
        return 0;
    }

    /**
     * Score hiking route come in hiking_routes.lua (name, wikidata, wikipedia, wikimedia_commons, ref + osm2cai_status).
     */
    public static function hikingRouteScore(array $tags): int
    {
        $s = 0;
        if (! empty($tags['name'])) {
            $s++;
        }
        if (! empty($tags['wikidata'])) {
            $s++;
        }
        if (! empty($tags['wikipedia'])) {
            $s++;
        }
        if (! empty($tags['wikimedia_commons'])) {
            $s++;
        }
        if (! empty($tags['ref'])) {
            $s++;
        }
        return $s + self::hikingRouteOsm2caiStatus($tags);
    }

    /**
     * Valori per le colonne hiking_routes derivati dai tag (come in Lua).
     *
     * @return array<string, mixed>
     */
    public static function hikingRouteColumnsFromTags(array $tags): array
    {
        return [
            'name' => $tags['name'] ?? null,
            'cai_scale' => $tags['cai_scale'] ?? null,
            'osm2cai_status' => self::hikingRouteOsm2caiStatus($tags),
            'score' => self::hikingRouteScore($tags),
            'osmc_symbol' => $tags['osmc:symbol'] ?? null,
            'network' => $tags['network'] ?? null,
            'survey_date' => $tags['survey:date'] ?? null,
            'roundtrip' => $tags['roundtrip'] ?? null,
            'symbol' => $tags['symbol'] ?? null,
            'symbol_it' => $tags['symbol:it'] ?? null,
            'ascent' => $tags['ascent'] ?? null,
            'descent' => $tags['descent'] ?? null,
            'distance' => $tags['distance'] ?? null,
            'duration_forward' => $tags['duration:forward'] ?? null,
            'duration_backward' => $tags['duration:backward'] ?? null,
            'from' => $tags['from'] ?? null,
            'to' => $tags['to'] ?? null,
            'rwn_name' => $tags['rwn:name'] ?? null,
            'ref_REI' => $tags['ref:REI'] ?? null,
            'maintenance' => $tags['maintenance'] ?? null,
            'maintenance_it' => $tags['maintenance:it'] ?? null,
            'operator' => $tags['operator'] ?? null,
            'state' => $tags['state'] ?? null,
            'ref' => $tags['ref'] ?? null,
            'source' => $tags['source'] ?? null,
            'source_ref' => $tags['source:ref'] ?? null,
            'note' => $tags['note'] ?? null,
            'note_it' => $tags['note:it'] ?? null,
            'old_ref' => $tags['old_ref'] ?? null,
            'note_project_page' => $tags['note:project_page'] ?? null,
            'website' => $tags['website'] ?? null,
            'wikimedia_commons' => $tags['wikimedia_commons'] ?? null,
            'description' => $tags['description'] ?? null,
            'description_it' => $tags['description:it'] ?? null,
        ];
    }
}
