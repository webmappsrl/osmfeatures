<?php

namespace App\Http\Controllers;

use App\Models\Pole;
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
     *     description="Returns a list of Poles with their details",
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
    public function list()
    {
        $poles = Pole::all(['osm_id', 'updated_at',])->mapWithKeys(function ($pole) {
            return [$pole->osm_id => $pole->updated_at];
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
