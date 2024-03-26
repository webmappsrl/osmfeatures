<?php

namespace App\Http\Controllers;

use App\Models\HikingRoute;
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
     *     description="Returns a list of Hiking Routes with their details",
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
    public function list()
    {
        $hikingRoutes = HikingRoute::all(['osm_id', 'updated_at'])->mapWithKeys(function ($route) {
            return [$route->osm_id => $route->updated_at->toIso8601String()];
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
     *         @OA\JsonContent(ref="#/components/schemas/HikingRouteGeojsonFeature")
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

        $geojson = [
            'type' => 'Feature',
            'properties' => [
                'name' => $hikingRoute->name ?? '',
                'osm_id' => $hikingRoute->osm_id,
                'osm_type' => $hikingRoute->osm_type,
                'description' => $hikingRoute->description ?? '',
                'tags' => $hikingRoute->tags ?? '{}',

            ],
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojson);
    }
}
