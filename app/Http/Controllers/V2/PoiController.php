<?php

namespace App\Http\Controllers\V2;

use App\Models\Poi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PoiController extends BaseV2Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/features/pois/list",
     *     operationId="listPoisV2",
     *     tags={"API V2"},
     *     summary="List all POIs (V2)",
     *     description="Returns a paginated list of POIs. Optionally filtered by updated_at, bbox. V2: pagination (1000 per page) and cache.",
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="updated_at", type="string", format="date-time"))),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bounding box not valid")
     * )
     *
     * Lista paginata di POI con supporto a updated_at e bbox.
     * V1 usava Poi::all() che carica tutto in memoria — qui si usa la paginazione.
     */
    public function list(Request $request): JsonResponse
    {
        if ($request->filled('bbox')) {
            $error = $this->validateBbox($request->query('bbox'));
            if ($error) {
                return $error;
            }
        }

        $cacheKey = $this->listCacheKey('pois', $request);

        $result = Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($request) {
            $query = DB::table('pois');

            if ($request->filled('updated_at')) {
                $query->where('updated_at', '>', $request->query('updated_at'));
            }

            if ($request->filled('bbox')) {
                $this->applyBboxFilter($query, $request->query('bbox'));
            }

            $paginator = $query->orderBy('updated_at', 'desc')
                ->paginate(self::PER_PAGE, ['osm_id', 'updated_at']);

            $paginator->getCollection()->transform(function ($poi) {
                return [
                    'id' => $poi->osm_id,
                    'updated_at' => Carbon::parse($poi->updated_at)->toIso8601String(),
                ];
            });

            return $paginator;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->withListCacheHeaders(response()->json($result));
    }

    /**
     * @OA\Get(
     *     path="/api/v2/features/pois/{id}",
     *     operationId="getPoiByIdV2",
     *     tags={"API V2"},
     *     summary="Get POI by ID (V2)",
     *     description="Returns a single POI in GeoJSON format. V2: ETag and Last-Modified for caching.",
     *     @OA\Parameter(name="id", in="path", required=true, description="POI numeric id (osm_id)", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="Feature"),
     *             @OA\Property(property="properties", type="object", @OA\Property(property="name", type="string"), @OA\Property(property="class", type="string"), @OA\Property(property="subclass", type="string"), @OA\Property(property="osm_id", type="integer"), @OA\Property(property="osm_type", type="string"), @OA\Property(property="tags", type="object")),
     *             @OA\Property(property="geometry", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="POI not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = "v2:pois:{$id}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL_SHOW, function () use ($id) {
            $poi = Poi::selectRaw('*, ST_AsGeoJSON(geom) as geojson_geom')
                ->where('osm_id', $id)
                ->first();

            if (!$poi) {
                return null;
            }

            $feature = [
                'type' => 'Feature',
                'properties' => [
                    'name' => $poi->name,
                    'class' => $poi->class,
                    'subclass' => $poi->subclass,
                    'osm_id' => $poi->osm_id,
                    'osm_type' => $poi->osm_type,
                    'tags' => $poi->tags,
                ],
                'geometry' => json_decode($poi->geojson_geom, true),
            ];

            return [
                'feature' => $feature,
                'etag' => md5($poi->geojson_geom . $poi->updated_at),
                'updated_at' => $poi->updated_at,
            ];
        });

        if (!$data) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return $this->withShowCacheHeaders(
            response()->json($data['feature']),
            $data['etag'],
            $data['updated_at']
        );
    }
}
