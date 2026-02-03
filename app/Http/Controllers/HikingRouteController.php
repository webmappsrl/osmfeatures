<?php

namespace App\Http\Controllers;

use App\Models\HikingRoute;
use App\Models\HikingWay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HikingRouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/hiking-routes/list",
     *     operationId="listHikingRoutes",
     *     tags={"API V1"},
     *     summary="List all Hiking Routes",
     *     description="Returns a list of Hiking Routes with their details. Optionally filtered by updated_at, bbox, score, etc. Paginated results are available.",
     *
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Parameter(ref="#/components/parameters/list_score"),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Hiking Routes that have osm2cai status greater than or equal to the specified value. Max status is 3.",
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
     *             type="object",
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/BaseListItem")
     *             }
     *         )
     *     )
     * )
     */
    public function list(Request $request)
    {
        $updated_at = $request->query('updated_at');
        $perPage = 1000;
        $bbox = $request->query('bbox');
        $score = $request->query('score');
        $osm2caiStatus = $request->query('status');
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

        if ($osm2caiStatus) {
            $query->where('osm2cai_status', '>=', $osm2caiStatus);
        }

        $hikingRoutes = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        $hikingRoutes->getCollection()->transform(function ($hr) {
            $hr->updated_at = Carbon::parse($hr->updated_at)->toIso8601String();
            $model = HikingRoute::find($hr->id);
            $hr->id = $model->getOsmFeaturesId();

            return $hr;
        });

        return response()->json($hikingRoutes);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/hiking-routes/{id}",
     *     operationId="getHikingRouteById",
     *     tags={"API V1"},
     *     summary="Get Hiking Routes by Osmfeatures ID",
     *     description="Returns a single Hiking Route in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Hiking Route Osmfeatures ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Return the Geojson Feature of the Hiking Route",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="Feature"),
     *             @OA\Property(
     *                 property="properties",
     *                 type="object",
     *                 @OA\Property(property="type", type="string", example="Feature"),
     *                 @OA\Property(property="osm_type", type="string", example="R"),
     *                 @OA\Property(property="osm_id", type="integer", example=4099576),
     *                 @OA\Property(property="updated_at_osm", type="string", format="date-time", example="2024-04-14 09:57:19"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-14T09:57:19.000000Z"),
     *                 @OA\Property(property="name", type="string", example="Via Alpina Red R116"),
     *                 @OA\Property(property="cai_scale", type="string", example=null),
     *                 @OA\Property(property="score", type="integer", example=2),
     *                 @OA\Property(property="osmc_symbol", type="string", example="black:red:white_triangle:V:blue"),
     *                 @OA\Property(property="network", type="string", example="iwn"),
     *                 @OA\Property(property="survey_date", type="string", format="date-time", example=null),
     *                 @OA\Property(property="roundtrip", type="string", example=null),
     *                 @OA\Property(property="symbol", type="string", example=null),
     *                 @OA\Property(property="symbol_it", type="string", example=null),
     *                 @OA\Property(property="ascent", type="integer", example=null),
     *                 @OA\Property(property="descent", type="integer", example=null),
     *                 @OA\Property(property="distance", type="number", example=null),
     *                 @OA\Property(property="duration_forward", type="string", example=null),
     *                 @OA\Property(property="duration_backward", type="string", example=null),
     *                 @OA\Property(property="from", type="string", example=null),
     *                 @OA\Property(property="to", type="string", example=null),
     *                 @OA\Property(property="rwn_name", type="string", example=null),
     *                 @OA\Property(property="ref_REI", type="string", example=null),
     *                 @OA\Property(property="maintenance", type="string", example=null),
     *                 @OA\Property(property="maintenance_it", type="string", example=null),
     *                 @OA\Property(property="operator", type="string", example="via-alpina.org"),
     *                 @OA\Property(property="state", type="string", example=null),
     *                 @OA\Property(property="ref", type="string", example="VA-R116"),
     *                 @OA\Property(property="source", type="string", example=null),
     *                 @OA\Property(property="source_ref", type="string", example=null),
     *                 @OA\Property(property="note", type="string", example=null),
     *                 @OA\Property(property="note_it", type="string", example=null),
     *                 @OA\Property(property="old_ref", type="string", example=null),
     *                 @OA\Property(property="note_project_page", type="string", example=null),
     *                 @OA\Property(property="website", type="string", example="http://www.via-alpina.org/en/stage/296"),
     *                 @OA\Property(property="wikimedia_commons", type="string", example=null),
     *                 @OA\Property(property="description", type="string", example="Bourg-St-Pierre - Col du Grand-Saint-Bernard"),
     *                 @OA\Property(property="description_it", type="string", example=null),
     *                 @OA\Property(
     *                     property="members",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="ref", type="integer"),
     *                         @OA\Property(property="role", type="string"),
     *                         @OA\Property(property="type", type="string")
     *                     ),
     *                     example={
     *                         {"ref": 298650101, "role": "", "type": "w"},
     *                         {"ref": 298650055, "role": "", "type": "w"}
     *                     }
     *                 ),
     *                 @OA\Property(property="members_ids", type="string", example="298650101,298650055"),
     *                 @OA\Property(
     *                     property="dem_enrichments",
     *                     type="object",
     *                     example={
     *                         "ascent": 1428,
     *                         "ele_to": 1356,
     *                         "descent": 958,
     *                         "ele_max": 2223,
     *                         "ele_min": 887,
     *                         "distance": 11.9,
     *                         "ele_from": 887,
     *                         "round_trip": false,
     *                         "duration_forward_bike": 315,
     *                         "duration_backward_bike": 240,
     *                         "duration_forward_hiking": 315,
     *                         "duration_backward_hiking": 270
     *                     }
     *                 ),
     *                 @OA\Property(
     *                     property="admin_areas",
     *                     type="object",
     *                     example={
     *                         "2": {{"osmfeatures_id": "R365331", "name": "Italia"}},
     *                         "4": {{"osmfeatures_id": "R41977", "name": "Toscana"}}
     *                     }
     *                 ),
     *                 @OA\Property(property="osmfeatures_id", type="string", example="R4099576"),
     *                 @OA\Property(property="osm_url", type="string", example="https://www.openstreetmap.org/relation/4099576"),
     *                 @OA\Property(property="osm_api", type="string", example="https://www.openstreetmap.org/api/0.6/relation/4099576.json"),
     *                 @OA\Property(
     *                     property="osm_tags",
     *                     type="object",
     *                     example={
     *                         "ref": "VA-R116",
     *                         "name": "Via Alpina Red R116",
     *                         "type": "route",
     *                         "route": "hiking",
     *                         "network": "iwn",
     *                         "website": "http://www.via-alpina.org/en/stage/296",
     *                         "operator": "via-alpina.org",
     *                         "description": "Bourg-St-Pierre - Col du Grand-Saint-Bernard",
     *                         "osmc:symbol": "black:red:white_triangle:V:blue"
     *                     }
     *                 ),
     *                 @OA\Property(property="wikidata", type="string", example=null),
     *                 @OA\Property(property="wikipedia", type="string", example=null)
     *             ),
     *             @OA\Property(
     *                 property="geometry",
     *                 type="object",
     *                 @OA\Property(property="type", type="string", example="LineString"),
     *                 @OA\Property(
     *                     property="coordinates",
     *                     type="array",
     *                     example={{100, 0}, {101, 1}},
     *                     @OA\Items(
     *                         type="array",
     *                         @OA\Items(type="number")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hiking Route not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $hr = HikingRoute::getOsmfeaturesByOsmfeaturesID($id);

        if (!$hr) {
            return response()->json(['message' => 'Hiking route not found'], 404);
        }

        $geojsonFeature = $hr->getGeojsonFeature();

        return response()->json($geojsonFeature);
    }



    public function osm(string $osmType, int $osmId)
    {
        $acceptedTypes = ['relation', 'way', 'node'];

        if (!in_array($osmType, $acceptedTypes)) {
            return response()->json(['error' => 'Bad Request'], 404);
        }

        $hikingRoute = HikingRoute::where('osm_type', strtoupper(substr($osmType, 0, 1)))->where('osm_id', $osmId)->first();

        if (!$hikingRoute) {
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
