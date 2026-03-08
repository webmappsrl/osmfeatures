<?php

namespace App\Services\V2;

use App\Models\HikingRoute;
use App\Models\Place;
use App\Models\Pole;
use Illuminate\Support\Facades\DB;
use Wm\WmPackage\Facades\OsmClient;
use Wm\WmPackage\Exceptions\OsmClientException;

class OsmRefreshService
{
    /**
     * Modelli da interrogare per risolvere (osm_type, osm_id), in ordine di priorità.
     *
     * @var array<int, array{slug: string, class: string}>
     */
    private const MODEL_MAP = [
        ['slug' => 'hiking-routes', 'class' => HikingRoute::class],
        ['slug' => 'places', 'class' => Place::class],
        ['slug' => 'poles', 'class' => Pole::class],
    ];

    /**
     * Converte osm_type (R/W/N) nel tipo per l'API OSM (relation/way/node).
     */
    private function osmTypeToApiType(string $osmType): string
    {
        return match (strtoupper($osmType)) {
            'R' => 'relation',
            'W' => 'way',
            'N' => 'node',
            default => throw new \InvalidArgumentException("Invalid osm_type: {$osmType}"),
        };
    }

    /**
     * Trova quale modello contiene il record (osm_type, osm_id).
     *
     * @return array{slug: string, model: \Illuminate\Database\Eloquent\Model}|null
     */
    public function resolveModel(string $osmType, string $osmId): ?array
    {
        foreach (self::MODEL_MAP as $entry) {
            $model = $entry['class']::where('osm_type', $osmType)->where('osm_id', $osmId)->first();
            if ($model !== null) {
                return ['slug' => $entry['slug'], 'model' => $model];
            }
        }

        return null;
    }

    /**
     * Indica in quale tabella inserire una feature non presente in DB (in base a osm_type e tag OSM).
     */
    private function inferSlugForInsert(string $osmType, array $tags): string
    {
        if (strtoupper($osmType) === 'N') {
            $isPole = ($tags['tourism'] ?? null) === 'information' && ($tags['information'] ?? null) === 'guidepost';
            $isPoleProposed = ($tags['proposed'] ?? null) === 'yes'
                && ($tags['proposed:information'] ?? null) === 'guidepost'
                && ($tags['proposed:tourism'] ?? null) === 'information';
            if ($isPole || $isPoleProposed) {
                return 'poles';
            }
            return 'places';
        }
        if (strtoupper($osmType) === 'W') {
            return 'places';
        }
        return 'hiking-routes';
    }

    /**
     * Esegue il refresh: se in DB aggiorna, altrimenti fetch da OSM, insert e ritorna.
     *
     * @return array{slug: string, osmfeatures_id: string}
     * @throws \InvalidArgumentException se osmfeatures_id non valido
     * @throws \RuntimeException se OSM API fallisce
     */
    public function refresh(string $osmfeaturesId): array
    {
        $osmType = substr($osmfeaturesId, 0, 1);
        $osmId = substr($osmfeaturesId, 1);

        if (! in_array(strtoupper($osmType), ['R', 'W', 'N'], true) || ! ctype_digit($osmId)) {
            throw new \InvalidArgumentException('Invalid osmfeatures_id format. Expected R123, W456, or N789.');
        }

        $apiType = $this->osmTypeToApiType($osmType);
        $osmid = $apiType . '/' . $osmId;

        try {
            [$properties, $geometry] = OsmClient::getPropertiesAndGeometry($osmid);
        } catch (OsmClientException $e) {
            throw new \RuntimeException('OSM API error: ' . $e->getMessage(), 502);
        }

        $updatedAt = $properties['_updated_at'] ?? now()->format('Y-m-d H:i:s');
        unset($properties['_updated_at'], $properties['_roundtrip']);
        $tags = $properties;
        $geomJson = json_encode($geometry);

        $resolved = $this->resolveModel($osmType, $osmId);

        if ($resolved !== null) {
            $this->performUpdate($resolved['slug'], $resolved['model']->getTable(), $osmType, $osmId, $geomJson, $tags, $updatedAt);
            return ['slug' => $resolved['slug'], 'osmfeatures_id' => $osmfeaturesId];
        }

        $slug = $this->inferSlugForInsert($osmType, $tags);
        $this->performInsert($slug, $osmType, $osmId, $geomJson, $tags, $updatedAt);

        return ['slug' => $slug, 'osmfeatures_id' => $osmfeaturesId];
    }

    private function performUpdate(string $slug, string $table, string $osmType, string $osmId, string $geomJson, array $tags, string $updatedAt): void
    {
        DB::transaction(function () use ($table, $osmType, $osmId, $geomJson, $tags, $updatedAt, $slug) {
            if ($slug === 'places') {
                [$class, $subclass] = OsmLuaLogic::placeClassSubclass($tags);
                $score = OsmLuaLogic::placeScore($tags);
                $name = $tags['name'] ?? null;
                $elevation = isset($tags['ele']) && is_numeric($tags['ele']) ? (int) $tags['ele'] : null;
                $geomExpr = in_array($osmType, ['W', 'R'], true)
                    ? 'ST_Centroid(ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326))'
                    : 'ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326)';
                DB::update("
                    UPDATE {$table}
                    SET name = ?, class = ?, subclass = ?, geom = {$geomExpr}, tags = ?::jsonb, updated_at = ?, score = ?, elevation = ?
                    WHERE osm_type = ? AND osm_id = ?
                ", [$name, $class, $subclass, $geomJson, json_encode($tags), $updatedAt, $score, $elevation, $osmType, $osmId]);
            } elseif ($slug === 'poles') {
                $score = OsmLuaLogic::poleScore($tags);
                $name = $tags['name'] ?? null;
                $ref = $tags['ref'] ?? null;
                $ele = $tags['ele'] ?? null;
                $destination = $tags['destination'] ?? null;
                $support = $tags['support'] ?? null;
                $elevation = is_numeric($ele ?? '') ? (int) $ele : null;
                DB::update("
                    UPDATE {$table}
                    SET name = ?, tags = ?::jsonb, geom = ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326), updated_at = ?,
                        ref = ?, ele = ?, destination = ?, support = ?, elevation = ?, score = ?
                    WHERE osm_type = ? AND osm_id = ?
                ", [$name, json_encode($tags), $geomJson, $updatedAt, $ref, $ele, $destination, $support, $elevation, $score, $osmType, $osmId]);
            } else {
                $cols = OsmLuaLogic::hikingRouteColumnsFromTags($tags);
                $geomExpr = 'ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326)';
                DB::update("
                    UPDATE {$table}
                    SET name = ?, cai_scale = ?, osm2cai_status = ?, score = ?, osmc_symbol = ?, network = ?, survey_date = ?, roundtrip = ?,
                        symbol = ?, symbol_it = ?, ascent = ?, descent = ?, distance = ?, duration_forward = ?, duration_backward = ?,
                        \"from\" = ?, \"to\" = ?, rwn_name = ?, ref_REI = ?, maintenance = ?, maintenance_it = ?, operator = ?, state = ?,
                        ref = ?, source = ?, source_ref = ?, note = ?, note_it = ?, old_ref = ?, note_project_page = ?, website = ?,
                        wikimedia_commons = ?, description = ?, description_it = ?, geom = {$geomExpr}, tags = ?::jsonb, updated_at = ?, updated_at_osm = ?
                    WHERE osm_type = ? AND osm_id = ?
                ", [
                    $cols['name'], $cols['cai_scale'], $cols['osm2cai_status'], $cols['score'], $cols['osmc_symbol'], $cols['network'], $cols['survey_date'], $cols['roundtrip'],
                    $cols['symbol'], $cols['symbol_it'], $cols['ascent'], $cols['descent'], $cols['distance'], $cols['duration_forward'], $cols['duration_backward'],
                    $cols['from'], $cols['to'], $cols['rwn_name'], $cols['ref_REI'], $cols['maintenance'], $cols['maintenance_it'], $cols['operator'], $cols['state'],
                    $cols['ref'], $cols['source'], $cols['source_ref'], $cols['note'], $cols['note_it'], $cols['old_ref'], $cols['note_project_page'], $cols['website'],
                    $cols['wikimedia_commons'], $cols['description'], $cols['description_it'], $geomJson, json_encode($tags), $updatedAt, $updatedAt,
                    $osmType, $osmId,
                ]);
            }
        });
    }

    private function performInsert(string $slug, string $osmType, string $osmId, string $geomJson, array $tags, string $updatedAt): void
    {
        DB::transaction(function () use ($slug, $osmType, $osmId, $geomJson, $tags, $updatedAt) {
            if ($slug === 'places') {
                [$class, $subclass] = OsmLuaLogic::placeClassSubclass($tags);
                $score = OsmLuaLogic::placeScore($tags);
                $name = $tags['name'] ?? null;
                $elevation = isset($tags['ele']) && is_numeric($tags['ele']) ? (int) $tags['ele'] : null;
                $geomExpr = in_array($osmType, ['W', 'R'], true)
                    ? 'ST_Centroid(ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326))'
                    : 'ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326)';
                DB::insert("
                    INSERT INTO places (osm_type, osm_id, name, class, subclass, geom, tags, updated_at, score, elevation)
                    VALUES (?, ?, ?, ?, ?, {$geomExpr}, ?::jsonb, ?, ?, ?)
                ", [$osmType, $osmId, $name, $class, $subclass, $geomJson, json_encode($tags), $updatedAt, $score, $elevation]);
            } elseif ($slug === 'poles') {
                $score = OsmLuaLogic::poleScore($tags);
                $name = $tags['name'] ?? null;
                $ref = $tags['ref'] ?? null;
                $ele = $tags['ele'] ?? null;
                $destination = $tags['destination'] ?? null;
                $support = $tags['support'] ?? null;
                $elevation = is_numeric($ele ?? '') ? (int) $ele : null;
                DB::insert("
                    INSERT INTO poles (osm_type, osm_id, name, tags, geom, updated_at, ref, ele, destination, support, elevation, score)
                    VALUES (?, ?, ?, ?::jsonb, ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326), ?, ?, ?, ?, ?, ?)
                ", [$osmType, $osmId, $name, json_encode($tags), $geomJson, $updatedAt, $ref, $ele, $destination, $support, $elevation, $score]);
            } else {
                $cols = OsmLuaLogic::hikingRouteColumnsFromTags($tags);
                DB::insert("
                    INSERT INTO hiking_routes (osm_type, osm_id, name, cai_scale, osm2cai_status, score, osmc_symbol, network, survey_date, roundtrip, symbol, symbol_it,
                        ascent, descent, distance, duration_forward, duration_backward, \"from\", \"to\", rwn_name, ref_REI, maintenance, maintenance_it, operator, state,
                        ref, source, source_ref, note, note_it, old_ref, note_project_page, website, wikimedia_commons, description, description_it,
                        geom, tags, updated_at, updated_at_osm)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ST_SetSRID(ST_GeomFromGeoJSON(?::json)::geometry, 4326), ?::jsonb, ?, ?)
                ", [
                    $osmType, $osmId, $cols['name'], $cols['cai_scale'], $cols['osm2cai_status'], $cols['score'], $cols['osmc_symbol'], $cols['network'], $cols['survey_date'], $cols['roundtrip'], $cols['symbol'], $cols['symbol_it'],
                    $cols['ascent'], $cols['descent'], $cols['distance'], $cols['duration_forward'], $cols['duration_backward'], $cols['from'], $cols['to'], $cols['rwn_name'], $cols['ref_REI'], $cols['maintenance'], $cols['maintenance_it'], $cols['operator'], $cols['state'],
                    $cols['ref'], $cols['source'], $cols['source_ref'], $cols['note'], $cols['note_it'], $cols['old_ref'], $cols['note_project_page'], $cols['website'], $cols['wikimedia_commons'], $cols['description'], $cols['description_it'],
                    $geomJson, json_encode($tags), $updatedAt, $updatedAt,
                ]);
            }
        });
    }
}
