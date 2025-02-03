<?php

namespace App\Http\Controllers;

use App\Models\Poi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="Nome della tua API",
 *     version="1.0.0",
 *     description="Api documentation",
 *     @OA\Contact(
 *         email="info@webmapp.it"
 *     )
 *   ),
 *   @OA\Server(
 *       url=L5_SWAGGER_CONST_HOST,
 *       description="API server"
 *   )
 * )
 * @OA\Schema(
 *     schema="PoiItem",
 *     type="object",
 *     @OA\Property(
 *         property="osm_id",
 *         type="integer",
 *         example=123
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2021-03-10T02:00:00Z"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="GeoJsonFeature",
 *     type="object",
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         example="Feature"
 *     ),
 *     @OA\Property(
 *         property="properties",
 *         type="object",
 *         @OA\Property(property="name", type="string", example="Duomo di Arezzo"),
 *         @OA\Property(property="class", type="string", example="amenity"),     
 *         @OA\Property(property="subclass", type="string", example="place_of_worship"),
 *         @OA\Property(property="osm_id", type="integer", example="22720153"),
 *         @OA\Property(property="osm_type", type="string", example="W")
 *     ),
 *     @OA\Property(
 *         property="geometry",
 *         type="object",
 *         @OA\Property(
 *             property="type",
 *             type="string",
 *             example="Point"
 *         ),
 *         @OA\Property(
 *             property="coordinates",
 *             type="array",
 *             @OA\Items(type="number")
 *         )
 *     )
 * )
 */
class PoiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/pois/list",
     *     operationId="listPois",
     *     tags={"POIs"},
     *     summary="List all POIs",
     *     description="Returns a list of POIs with their IDs and updated timestamps",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/PoiItem")
     *         ),
     *     ),
     * )
     */
    public function list()
    {
        $pois = Poi::all(['osm_id', 'updated_at'])->mapWithKeys(function ($poi) {
            return [$poi->osm_id => $poi->updated_at->toIso8601String()];
        });

        return response()->json($pois);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/pois/{id}",
     *     operationId="getPoiById",
     *     tags={"POIs"},
     *     summary="Get POI by ID",
     *     description="Returns a single POI in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="POI ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/PoiGeoJsonFeature")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="POI not found"
     *     )
     * )
     */
    public function show($id)
    {
        $poi = Poi::where('id', $id)->first();

        if (!$poi) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$poi->geom])[0]->geojson;
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => [
                'name' => $poi->name,
                'class' => $poi->class,
                'subclass' => $poi->subclass,
                'osm_id' => $poi->osm_id,
                'osm_type' => $poi->osm_type,
                'tags' => $poi->tags,
            ],
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}
