<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/places/list",
     *     operationId="listPlaces",
     *     tags={"Places"},
     *     summary="List all Places",
     *     description="Returns a list of Places with their details. Optionally, provide an 'updated_at' parameter to filter places updated after the specified date.",
     *     @OA\Parameter(
     *         name="updated_at",
     *         in="query",
     *         description="Filter by the updated timestamp. Only places updated after this date will be returned. The date should be in ISO 8601 format.",
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
     *             @OA\Items(ref="#/components/schemas/PlaceItem")
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        $updated_at = $request->query('updated_at');
        $perPage = 100;
        $bbox = $request->query('bbox');

        $query = Place::query();

        if ($updated_at) {
            $query->where('updated_at', '>', $updated_at);
        }

        if ($bbox) {
            $bbox = explode(',', $bbox);
            $query->whereRaw('ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))', $bbox);
        }

        $places = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        return response()->json($places);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/places/{id}",
     *     operationId="getPlaceById",
     *     tags={"Places"},
     *     summary="Get Place by ID",
     *     description="Returns a single Place in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Place ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/GeoJsonFeature")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Place not found"
     *     )
     * )
     */
    public function show($id)
    {
        $place = Place::where('id', $id)->first();

        if (! $place) {
            return response()->json(['message' => 'place non trovato'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$place->geom])[0]->geojson;
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => [
                'name' => $place->name,
                'class' => $place->class,
                'subclass' => $place->subclass,
                'osm_id' => $place->osm_id,
                'osm_type' => $place->osm_type,
                'tags' => $place->tags,
            ],
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}
