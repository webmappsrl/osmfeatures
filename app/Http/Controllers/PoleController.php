<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/poles/list",
     *     operationId="listPoles",
     *     tags={"Poles"},
     *     summary="List all Poles",
     *     description="Returns a list of Poles with their details. Optionally, provide an 'updated_at' parameter to filter poles updated after the specified date.",
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
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/PoleItem")
     *         ),
     *     ),
     * )
     */

    public function list(Request $request)
    {
        $updated_after = $request->query('updated_at');

        $query = Pole::query();

        if ($updated_after) {
            $query->where('updated_at', '>', $updated_after);
        }

        $poles = $query->get(['osm_id', 'updated_at'])->mapWithKeys(function ($pole) {
            return [$pole->osm_id => $pole->updated_at->toIso8601String()];
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
     *         @OA\JsonContent(ref="#/components/schemas/PolesGeoJsonFeature")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pole not found"
     *     )
     * )
     */
    public function show($id)
    {
        $pole = Pole::where('osm_id', $id)->first();

        if (!$pole) {
            return response()->json(['message' => 'Pole not found'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$pole->geom])[0]->geojson;
        $properties = $pole->toArray();
        unset($properties['geom']);
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}
