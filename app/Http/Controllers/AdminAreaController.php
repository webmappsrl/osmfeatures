<?php

namespace App\Http\Controllers;

use App\Models\AdminArea;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdminAreaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/admin-areas/list",
     *     operationId="listAdminAreas",
     *     tags={"AdminAreas"},
     *     summary="List all Admin Areas",
     *     description="Returns a list of Admin Areas with their details. Optionally, provide an 'updated_at' parameter to filter areas updated after the specified date.",
     *     @OA\Parameter(
     *         name="updated_after",
     *         in="query",
     *         description="Filter by the updated timestamp. Only areas updated after this date will be returned. The date should be in ISO 8601 format.",
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
     *             @OA\Items(ref="#/components/schemas/AdminAreaItem")
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        $query = AdminArea::query();

        $updated_at = $request->query('updated_at');

        if ($updated_at) {
            $query->where('updated_at', '>', $updated_at);
        }

        $adminAreas = $query->get(['osm_id', 'updated_at'])->mapWithKeys(function ($area) {
            return [$area->osm_id => $area->updated_at->toIso8601String()];
        });

        return response()->json($adminAreas);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/admin-areas/{id}",
     *     operationId="getAdminAreaById",
     *     tags={"AdminAreas"},
     *     summary="Get Admin Area by ID",
     *     description="Returns a single Admin Area in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Admin Area ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/AdminAreaGeojsonFeature")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Admin Area not found"
     *     )
     * )
     */
    public function show($id)
    {
        $adminArea = AdminArea::where('osm_id', $id)->first();

        if (!$adminArea) {
            return response()->json(['message' => 'Admin Area non trovato'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$adminArea->geom])[0]->geojson;
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => [
                'name' => $adminArea->name,
                'osm_id' => $adminArea->osm_id,
                'osm_type' => $adminArea->osm_type,
                'admin_level' => $adminArea->admin_level,
                'tags' => $adminArea->tags,
            ],
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}
