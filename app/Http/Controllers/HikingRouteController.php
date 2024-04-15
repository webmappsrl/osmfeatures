<?php

namespace App\Http\Controllers;

use App\Models\HikingRoute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HikingRouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/hiking-routes/list",
     *     operationId="listHikingRoutes",
     *     tags={"HikingRoutes"},
     *     summary="List all Hiking Routes",

     *     description="Returns a list of Hiking Routes with their details. Optionally, provide an 'updated_at' parameter to filter routes updated after the specified date.",
     *     @OA\Parameter(
     *         name="updated_at",
     *         in="query",
     *         description="Filter by the updated timestamp. Only routes updated after this date will be returned. The date should be in ISO 8601 format.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time",
     *             example="2021-03-10T02:00:00Z"
     *         )
     *     ),
     * @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number to retrieve. Each page contains 100 results.",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example="1"
     *         )
     *     ),

     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/HikingRouteItem")
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        $updated_at = $request->query('updated_at');
        $perPage = 100;
        $bbox = $request->query('bbox');
        $score = $request->query('score');
        $isTest = $request->query('testdata');

        $query = DB::table('hiking_routes');

        if ($updated_at) {
            $query->where('updated_at', '>', $updated_at);
        }

        if ($bbox) {
            $bbox = explode(',', $bbox);
            // Check if the bbox is valid
            if (count($bbox) !== 4) {
                return response()->json(['message' => 'Bounding box non valido'], 400);
            }
            $bbox = array_map('floatval', $bbox);
            if ($isTest) {
                $query->whereRaw('ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
            } else {
                $query->whereRaw('ST_Intersects(ST_Transform(geom, 4326), ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
            }
        }

        if ($score) {
            $query->where('score', '>=', $score);
        }

        $hikingRoutes = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        $hikingRoutes->getCollection()->transform(function ($hr) {
            $hr->updated_at = Carbon::parse($hr->updated_at)->toIso8601String();

            return $hr;
        });

        return response()->json($hikingRoutes);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/hiking-routes/{id}",
     *     operationId="getHikingRouteById",
     *     tags={"HikingRoutes"},
     *     summary="Get Hiking Route by ID",
     *     description="Returns a single Hiking Route in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Hiking Route ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hiking Route not found"
     *     )
     * )
     */
    public function show($id)
    {
        $hikingRoute = HikingRoute::find($id);

        if ($hikingRoute === null) {
            return response()->json(['error' => 'Hiking Route not found'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$hikingRoute->geom])[0]->geojson;

        match ($hikingRoute->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        $properties = $hikingRoute->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$hikingRoute->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$hikingRoute->osm_id.json";
        $properties['osm_tags'] = json_decode($hikingRoute->tags, true);
        $properties['members'] = json_decode($hikingRoute->members, true);
        $properties['wikidata'] = $hikingRoute->getWikidataUrl();
        $properties['wikipedia'] = $hikingRoute->getWikipediaUrl();
        $properties['wikimedia_commons'] = $hikingRoute->getWikimediaCommonsUrl();

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/hiking-routes/osm/{osmtype}/{osmid}",
     *     operationId="getHikingRouteByOsmId",
     *     tags={"HikingRoutes"},
     *     summary="Get Hiking Route by OSM ID",
     *     description="Returns a single Hiking Route in GeoJSON format",
     *     @OA\Parameter(
     *         name="osmtype",
     *         description="OSM Type (node, way, relation)",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="osmid",
     *         description="OSM ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hiking Route not found"
     *     )
     * )
     */
    public function osm(string $osmType, int $osmId)
    {
        $acceptedTypes = ['relation', 'way', 'node'];

        if (! in_array($osmType, $acceptedTypes)) {
            return response()->json(['error' => 'Bad Request'], 404);
        }

        $hikingRoute = HikingRoute::where('osm_type', strtoupper(substr($osmType, 0, 1)))->where('osm_id', $osmId)->first();

        if (! $hikingRoute) {
            return response()->json(['error' => 'Hiking Route not found'], 404);
        }

        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$hikingRoute->geom])[0]->geojson;

        match ($hikingRoute->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        $properties = $hikingRoute->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$hikingRoute->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$hikingRoute->osm_id.json";
        $properties['osm_tags'] = json_decode($hikingRoute->tags, true);
        $properties['members'] = json_decode($hikingRoute->members, true);
        $properties['wikidata'] = $hikingRoute->getWikidataUrl();
        $properties['wikipedia'] = $hikingRoute->getWikipediaUrl();
        $properties['wikimedia_commons'] = $hikingRoute->getWikimediaCommonsUrl();

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}
