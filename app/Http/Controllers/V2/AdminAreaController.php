<?php

namespace App\Http\Controllers\V2;

use App\Models\AdminArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminAreaController extends BaseV2Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/features/admin-areas/list",
     *     operationId="listAdminAreasV2",
     *     tags={"API V2"},
     *     summary="List all Admin Areas (V2)",
     *     description="Returns a list of Admin Areas. Optionally filtered by updated_at, bbox and score. Paginated (1000 per page). V2: response cached with Cache-Control.",
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Parameter(ref="#/components/parameters/list_score"),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(allOf={@OA\Schema(ref="#/components/schemas/BaseListItem")})
     *     ),
     *     @OA\Response(response=400, description="Bounding box not valid"),
     *     @OA\Response(response=422, description="Validation errors")
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

        $cacheKey = $this->listCacheKey('admin-areas', $request);

        $result = Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($request) {
            $query = DB::table('admin_areas');

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
     *     path="/api/v2/features/admin-areas/{id}",
     *     operationId="getAdminAreaByIdV2",
     *     tags={"API V2"},
     *     summary="Get Admin Area by Osmfeatures ID (V2)",
     *     description="Returns a single Admin Area in GeoJSON format. V2: ETag and Last-Modified headers for caching.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Admin Area Osmfeatures ID", @OA\Schema(type="string")),
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
     *     @OA\Response(response=404, description="Admin Area not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = "v2:admin-areas:{$id}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL_SHOW, function () use ($id) {
            $osmType = substr($id, 0, 1);
            $osmId = substr($id, 1);

            $adminArea = AdminArea::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
                ->with('enrichment')
                ->where('osm_type', $osmType)
                ->where('osm_id', $osmId)
                ->first();

            if (!$adminArea) {
                return null;
            }

            $feature = $adminArea->getGeojsonFeatureV2($adminArea->geojson_geom);

            return [
                'feature' => $feature,
                'etag' => md5($adminArea->geojson_geom . $adminArea->updated_at),
                'updated_at' => $adminArea->updated_at,
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

    /**
     * @OA\Post(
     *     path="/api/v2/features/admin-areas/geojson",
     *     operationId="intersectingGeojsonAdminAreaV2",
     *     tags={"API V2"},
     *     summary="Admin Areas intersecting GeoJSON (V2)",
     *     description="Returns Admin Areas whose geometries intersect the provided GeoJSON. Optional filters: updated_at, admin_level, score.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"geojson"},
     *             @OA\Property(property="geojson", type="object", @OA\Property(property="geometry", type="object")),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="admin_level", type="integer"),
     *             @OA\Property(property="score", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="FeatureCollection"),
     *             @OA\Property(property="features", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation errors"),
     *     @OA\Response(response=500, description="Error processing GeoJSON intersection")
     * )
     *
     * Restituisce le AdminArea che intersecano il GeoJSON fornito.
     * V2: ST_AsGeoJSON calcolato inline → nessuna N+1 query.
     */
    public function intersectingGeojson(Request $request): JsonResponse
    {
        $allowedParams = ['geojson', 'updated_at', 'admin_level', 'score'];
        $invalidParams = array_diff(array_keys($request->all()), $allowedParams);

        if (!empty($invalidParams)) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => ['invalid_parameters' => 'Parametri non consentiti: ' . implode(', ', $invalidParams)],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'geojson' => 'required|array',
            'geojson.geometry' => 'required|array',
            'geojson.geometry.type' => 'required|string|in:Point,LineString,Polygon,MultiPoint,MultiLineString,MultiPolygon',
            'geojson.geometry.coordinates' => 'required|array',
            'updated_at' => 'nullable|date_format:Y-m-d\TH:i:s\Z',
            'admin_level' => 'nullable|integer|min:1|max:10',
            'score' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        try {
            if (!isset($request->geojson['geometry']) || !is_array($request->geojson['geometry'])) {
                return response()->json([
                    'message' => 'Validation errors',
                    'errors' => ['geojson' => ['Struttura GeoJSON non valida']],
                ], 422);
            }

            // V2: geojson_geom calcolato inline — zero query aggiuntive per la geometria
            $results = AdminArea::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
                ->with('enrichment')
                ->whereRaw('ST_Intersects(geom, ST_GeomFromGeoJSON(?))', [json_encode($request->geojson['geometry'])])
                ->when($request->updated_at, fn($q) => $q->where('updated_at', '>=', $request->updated_at))
                ->when($request->admin_level, fn($q) => $q->where('admin_level', $request->admin_level))
                ->when($request->score, fn($q) => $q->where('score', '>=', $request->score))
                ->get();

            $features = $results->map(fn($adminArea) => $adminArea->getGeojsonFeatureV2($adminArea->geojson_geom));

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Errore durante l\'elaborazione dell\'intersezione GeoJSON',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
