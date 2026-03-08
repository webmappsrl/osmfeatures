<?php

namespace App\Http\Controllers\V2;

use App\Models\HikingRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HikingRouteController extends BaseV2Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/features/hiking-routes/list",
     *     operationId="listHikingRoutesV2",
     *     tags={"API V2"},
     *     summary="List all Hiking Routes (V2)",
     *     description="Returns a list of Hiking Routes. Optionally filtered by updated_at, bbox, score, status (osm2cai). Paginated (1000 per page). V2: cached.",
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Parameter(ref="#/components/parameters/list_score"),
     *     @OA\Parameter(name="status", in="query", required=false, description="osm2cai status (min value)", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(allOf={@OA\Schema(ref="#/components/schemas/BaseListItem")})),
     *     @OA\Response(response=400, description="Bounding box not valid")
     * )
     */
    public function list(Request $request): JsonResponse
    {
        if ($request->filled('bbox')) {
            $error = $this->validateBbox($request->query('bbox'));
            if ($error) {
                return $error;
            }
        }

        $cacheKey = $this->listCacheKey('hiking-routes', $request);

        $result = Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($request) {
            $query = DB::table('hiking_routes');

            if ($request->filled('updated_at')) {
                $query->where('updated_at', '>', $request->query('updated_at'));
            }

            if ($request->filled('bbox')) {
                $this->applyBboxFilter($query, $request->query('bbox'));
            }

            if ($request->filled('score')) {
                $query->where('score', '>=', $request->query('score'));
            }

            if ($request->filled('status')) {
                $query->where('osm2cai_status', '>=', $request->query('status'));
            }

            $paginator = $query->orderBy('updated_at', 'desc')
                ->paginate(self::PER_PAGE, ['id', 'updated_at', 'osm_type', 'osm_id']);

            return $this->transformListCollection($paginator);
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->withListCacheHeaders(response()->json($result));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/features/hiking-routes/{id}",
     *     operationId="getHikingRouteByIdV2",
     *     tags={"API V2"},
     *     summary="Get Hiking Route by Osmfeatures ID (V2)",
     *     description="Returns a single Hiking Route in GeoJSON format. V2: ETag and Last-Modified for caching.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Hiking Route Osmfeatures ID", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="Feature"),
     *             @OA\Property(property="properties", type="object"),
     *             @OA\Property(property="geometry", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Hiking Route not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = "v2:hiking-routes:{$id}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL_SHOW, function () use ($id) {
            $osmType = substr($id, 0, 1);
            $osmId = substr($id, 1);

            // V2: geom calcolata inline + eager loading di entrambi gli enrichment
            // → da 4 query potenziali (v1) a 3 query totali (1 HR + 1 demEnrichment + 1 adminAreasEnrichment)
            $hr = HikingRoute::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
                ->with(['demEnrichment', 'adminAreasEnrichment'])
                ->where('osm_type', $osmType)
                ->where('osm_id', $osmId)
                ->first();

            if (!$hr) {
                return null;
            }

            $feature = $hr->getGeojsonFeatureV2($hr->geojson_geom);

            return [
                'feature' => $feature,
                'etag' => md5($hr->geojson_geom . $hr->updated_at),
                'updated_at' => $hr->updated_at,
            ];
        });

        if (!$data) {
            return response()->json(['message' => 'Hiking route not found'], 404);
        }

        return $this->withShowCacheHeaders(
            response()->json($data['feature']),
            $data['etag'],
            $data['updated_at']
        );
    }
}
