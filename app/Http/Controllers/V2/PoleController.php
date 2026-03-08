<?php

namespace App\Http\Controllers\V2;

use App\Models\Pole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PoleController extends BaseV2Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/features/poles/list",
     *     operationId="listPolesV2",
     *     tags={"API V2"},
     *     summary="List all Poles (V2)",
     *     description="Returns a list of Poles. Optionally filtered by updated_at, bbox, score. Paginated (1000 per page). V2: cached.",
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Parameter(ref="#/components/parameters/list_score"),
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

        $cacheKey = $this->listCacheKey('poles', $request);

        $result = Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($request) {
            $query = DB::table('poles');

            if ($request->filled('updated_at')) {
                $query->where('updated_at', '>', $request->query('updated_at'));
            }

            if ($request->filled('bbox')) {
                $this->applyBboxFilter($query, $request->query('bbox'));
            }

            if ($request->filled('score')) {
                $query->where('score', '>=', $request->query('score'));
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
     *     path="/api/v2/features/poles/{id}",
     *     operationId="getPoleByIdV2",
     *     tags={"API V2"},
     *     summary="Get Pole by Osmfeatures ID (V2)",
     *     description="Returns a single Pole in GeoJSON format. V2: ETag and Last-Modified for caching.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Pole Osmfeatures ID", @OA\Schema(type="string")),
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
     *     @OA\Response(response=404, description="Pole not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = "v2:poles:{$id}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL_SHOW, function () use ($id) {
            $osmType = substr($id, 0, 1);
            $osmId = substr($id, 1);

            $pole = Pole::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
                ->where('osm_type', $osmType)
                ->where('osm_id', $osmId)
                ->first();

            if (!$pole) {
                return null;
            }

            $feature = $pole->getGeojsonFeatureV2($pole->geojson_geom);

            return [
                'feature' => $feature,
                'etag' => md5($pole->geojson_geom . $pole->updated_at),
                'updated_at' => $pole->updated_at,
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
