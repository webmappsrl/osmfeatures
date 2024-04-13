<?php

namespace App\Http\Controllers;

use App\Models\Pole;
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
        $query = DB::table('poles');

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
            $query->whereRaw('ST_Intersects(ST_Transform(geom, 4326), ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
        }

        $poles = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

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
    public function show($id)
    {
        $pole = Pole::where('id', $id)->first();

        if (!$pole) {
            return response()->json(['message' => 'Pole not found'], 404);
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
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$pole->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$pole->osm_id.json";
        $properties['osm_tags'] = json_decode($pole->tags, true);

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return response()->json($geojsonFeature);
    }
}
