<?php

namespace App\Http\Controllers\V2;

use App\Models\HikingRoute;
use App\Models\Place;
use App\Models\Pole;
use App\Services\V2\OsmRefreshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class RefreshController extends BaseV2Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/features/refresh/{id}",
     *     operationId="refreshFeatureV2",
     *     tags={"API V2"},
     *     summary="Refresh single feature from OSM API",
     *     description="Fetches the feature from OpenStreetMap API and updates geom, tags and updated_at in the database. No PBF/Geofabrik. Supported: hiking-routes (R), places (N/W/R), poles (N).",
     *     @OA\Parameter(name="id", in="path", required=true, description="Osmfeatures ID (e.g. R1234567, W987654, N111222)", @OA\Schema(type="string", example="R1234567")),
     *     @OA\Response(response=200, description="Feature refreshed and returned", @OA\JsonContent(type="object", @OA\Property(property="type", type="string", example="Feature"), @OA\Property(property="properties", type="object"), @OA\Property(property="geometry", type="object"))),
     *     @OA\Response(response=404, description="Feature not found in database or on OSM"),
     *     @OA\Response(response=422, description="Invalid osmfeatures_id format"),
     *     @OA\Response(response=502, description="OSM API error")
     * )
     */
    public function refresh(string $id, OsmRefreshService $refreshService): JsonResponse
    {
        if (! preg_match('/^[RNW]\d+$/i', $id)) {
            return response()->json([
                'message' => 'Invalid osmfeatures_id format. Expected R, W, or N followed by digits (e.g. R1234567).',
            ], 422);
        }

        try {
            $result = $refreshService->refresh($id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            if ((int) $e->getCode() === 404) {
                return response()->json(['message' => 'Feature not found in database.'], 404);
            }

            return response()->json(['message' => $e->getMessage()], 502);
        }

        $slug = $result['slug'];
        $osmfeaturesId = $result['osmfeatures_id'];

        Cache::forget("v2:{$slug}:{$osmfeaturesId}");

        $data = $this->fetchFeatureForResponse($slug, $osmfeaturesId);
        if ($data === null) {
            return response()->json(['message' => 'Feature not found after refresh.'], 500);
        }

        return $this->withShowCacheHeaders(
            response()->json($data['feature']),
            $data['etag'],
            $data['updated_at']
        );
    }

    /**
     * Carica la feature aggiornata (stesso formato della show V2) per la risposta.
     *
     * @return array{feature: array, etag: string, updated_at: string}|null
     */
    private function fetchFeatureForResponse(string $slug, string $id): ?array
    {
        $osmType = substr($id, 0, 1);
        $osmId = substr($id, 1);

        return match ($slug) {
            'hiking-routes' => $this->fetchHikingRouteFeature($osmType, $osmId),
            'places' => $this->fetchPlaceFeature($osmType, $osmId),
            'poles' => $this->fetchPoleFeature($osmType, $osmId),
            default => null,
        };
    }

    private function fetchHikingRouteFeature(string $osmType, string $osmId): ?array
    {
        $hr = HikingRoute::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
            ->with(['demEnrichment', 'adminAreasEnrichment'])
            ->where('osm_type', $osmType)
            ->where('osm_id', $osmId)
            ->first();

        if (! $hr) {
            return null;
        }

        return [
            'feature' => $hr->getGeojsonFeatureV2($hr->geojson_geom),
            'etag' => md5($hr->geojson_geom . $hr->updated_at),
            'updated_at' => $hr->updated_at,
        ];
    }

    private function fetchPlaceFeature(string $osmType, string $osmId): ?array
    {
        $place = Place::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
            ->with('enrichment')
            ->where('osm_type', $osmType)
            ->where('osm_id', $osmId)
            ->first();

        if (! $place) {
            return null;
        }

        return [
            'feature' => $place->getGeojsonFeatureV2($place->geojson_geom),
            'etag' => md5($place->geojson_geom . $place->updated_at),
            'updated_at' => $place->updated_at,
        ];
    }

    private function fetchPoleFeature(string $osmType, string $osmId): ?array
    {
        $pole = Pole::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
            ->where('osm_type', $osmType)
            ->where('osm_id', $osmId)
            ->first();

        if (! $pole) {
            return null;
        }

        return [
            'feature' => $pole->getGeojsonFeatureV2($pole->geojson_geom),
            'etag' => md5($pole->geojson_geom . $pole->updated_at),
            'updated_at' => $pole->updated_at,
        ];
    }
}
