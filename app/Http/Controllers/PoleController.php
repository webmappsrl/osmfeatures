<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/poles/list",
     *     operationId="listPoles",
     *     tags={"Poles"},
     *     summary="List all Poles",
     *     description="Returns a paginated list of Poles with their details. Optionally, provide an 'updated_at' parameter to filter poles updated after the specified date. Supports pagination with 100 results per page.",
     *     @OA\Parameter(
     *         name="updated_at",
     *         in="query",
     *         description="Filter by the updated timestamp. Only poles updated after this date will be returned. The date should be in ISO 8601 format.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time",
     *             example="2021-03-10T02:00:00Z"
     *         )
     *     ),
     *     @OA\Parameter(
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
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PoleItem")
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        $updated_after = $request->query('updated_at');
        $perPage = 100;
        $bbox = $request->query('bbox');
        $score = $request->query('score');
        $query = DB::table('poles');
        $isTest = $request->query('testdata');

        if ($updated_after) {
            $query->where('updated_at', '>', $updated_after);
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

        $poles = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        $poles->getCollection()->transform(function ($pole) {
            $pole->updated_at = Carbon::parse($pole->updated_at)->toIso8601String();
            $model = Pole::find($pole->id);
            $pole->id = $model->getOsmfeaturesId();

            return $pole;
        });

        return response()->json($poles);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/poles/{id}",
     *     operationId="getPoleById",
     *     tags={"Poles"},
     *     summary="Get Pole by ID",
     *     description="Returns a single Pole in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Pole ID",
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
     *         description="Pole not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $pole = Pole::getOsmfeaturesByOsmfeaturesID($id);

        if (!$pole) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geojsonFeature = $pole->getGeojsonFeature();

        return response()->json($geojsonFeature);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/poles/osm/{osmtype}/{osmid}",
     *     operationId="getPoleByOsmId",
     *     tags={"Poles"},
     *     summary="Get Pole by OSM ID",
     *     description="Returns a single Pole in GeoJSON format",
     *     @OA\Parameter(
     *         name="osmtype",
     *         description="OSM Type (relation, way, node)",
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
     *         description="Pole not found"
     *     )
     * )
     */
    public function osm(string $osmType, int $osmid)
    {
        $acceptedTypes = ['relation', 'way', 'node'];

        if (!in_array($osmType, $acceptedTypes)) {
            return response()->json(['message' => 'Bad request'], 404);
        }

        $pole = Pole::where('osm_type', strtoupper(substr($osmType, 0, 1)))
            ->where('osm_id', $osmid)
            ->first();

        if (!$pole) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$pole->geom])[0]->geojson;
        match ($pole->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        $properties = $pole->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        unset($properties['elevation']);
        $properties['osm_url'] = $pole->getOsmUrl();
        $properties['osm_api'] = $pole->getOsmApiUrl();
        $properties['osm_tags'] = json_decode($pole->tags, true);
        $properties['wikidata'] = $pole->getWikidataUrl();
        $properties['wikipedia'] = $pole->getWikipediaUrl();
        $properties['wikimedia_commons'] = $pole->getWikimediaCommonsUrl();

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return response()->json($geojsonFeature);
    }
}
