<?php

namespace App\Http\Controllers\V2;

use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceController extends BaseV2Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/features/places/list",
     *     operationId="listPlacesV2",
     *     tags={"API V2"},
     *     summary="List all Places (V2)",
     *     description="Returns a list of Places. Optionally filtered by updated_at, bbox, score. Paginated (1000 per page). V2: cached.",
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

        $cacheKey = $this->listCacheKey('places', $request);

        $result = Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($request) {
            $query = DB::table('places');

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
     *     path="/api/v2/features/places/{id}",
     *     operationId="getPlaceByIdV2",
     *     tags={"API V2"},
     *     summary="Get Place by Osmfeatures ID (V2)",
     *     description="Returns a single Place in GeoJSON format. V2: ETag and Last-Modified for caching.",
     *     @OA\Parameter(name="id", in="path", required=true, description="Place Osmfeatures ID", @OA\Schema(type="string")),
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
     *     @OA\Response(response=404, description="Place not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $cacheKey = "v2:places:{$id}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL_SHOW, function () use ($id) {
            $osmType = substr($id, 0, 1);
            $osmId = substr($id, 1);

            $place = Place::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
                ->with('enrichment')
                ->where('osm_type', $osmType)
                ->where('osm_id', $osmId)
                ->first();

            if (!$place) {
                return null;
            }

            $feature = $place->getGeojsonFeatureV2($place->geojson_geom);

            return [
                'feature' => $feature,
                'etag' => md5($place->geojson_geom . $place->updated_at),
                'updated_at' => $place->updated_at,
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
     * @OA\Get(
     *     path="/api/v2/features/places/{lon}/{lat}/{distance}",
     *     operationId="getPlacesByDistanceV2",
     *     tags={"API V2"},
     *     summary="Get nearby places (V2)",
     *     description="Returns places within the specified distance (meters) from the given coordinates. V2: ST_DWithin on geography, cached.",
     *     @OA\Parameter(name="lon", in="path", required=true, description="Longitude (WGS84)", @OA\Schema(type="number", example=10.494953)),
     *     @OA\Parameter(name="lat", in="path", required=true, description="Latitude (WGS84)", @OA\Schema(type="number", example=46.179482)),
     *     @OA\Parameter(name="distance", in="path", required=true, description="Distance in meters", @OA\Schema(type="integer", example=1000)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="osmfeatures_id", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="class", type="string"),
     *                 @OA\Property(property="subclass", type="string"),
     *                 @OA\Property(property="elevation", type="number"),
     *                 @OA\Property(property="distance", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid parameters")
     * )
     *
     * Ricerca place per prossimità.
     * V2: usa ST_DWithin su geography (usa indice spaziale, calcola distanza reale in metri).
     */
    public function getPlacesByDistance(string $lon, string $lat, int $distance): JsonResponse
    {
        if (!is_numeric($lon)) {
            return response()->json(['message' => 'Longitudine non valida'], 400);
        }
        if (!is_numeric($lat)) {
            return response()->json(['message' => 'Latitudine non valida'], 400);
        }
        if ($distance <= 0) {
            return response()->json(['message' => 'Distanza non valida'], 400);
        }

        $cacheKey = "v2:places:distance:{$lon}:{$lat}:{$distance}";

        $places = Cache::remember($cacheKey, self::CACHE_TTL_LIST, function () use ($lon, $lat, $distance) {
            try {
                return DB::table('places')
                    ->selectRaw("
                        osm_type || osm_id AS osmfeatures_id,
                        name,
                        class,
                        subclass,
                        elevation,
                        ROUND(
                            ST_Distance(
                                ST_Transform(geom, 4326)::geography,
                                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                            )
                        )::integer AS distance
                    ", [$lon, $lat])
                    ->whereRaw("
                        ST_DWithin(
                            ST_Transform(geom, 4326)::geography,
                            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                            ?
                        )
                    ", [$lon, $lat, $distance])
                    ->orderBy('distance')
                    ->limit(500)
                    ->get();
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                return null;
            }
        });

        if ($places === null) {
            return response()->json(['message' => 'Bad Request'], 400);
        }

        return $this->withListCacheHeaders(response()->json($places));
    }
}
