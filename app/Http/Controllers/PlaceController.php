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
     *     description="Returns a list of Places with their details",
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
    public function list()
    {
        $places = Place::all(['osm_id', 'updated_at'])->mapWithKeys(function ($place) {
            return [$place->osm_id => $place->updated_at->toIso8601String()];
        });

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
        $place = Place::where('osm_id', $id)->first();

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
