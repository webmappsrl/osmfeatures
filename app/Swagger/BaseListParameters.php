<?php

namespace App\Swagger;

/**
 * @OA\Parameter(
 *     parameter="list_updated_at",
 *     in="query",
 *     name="updated_at",
 *     description="Returns elements updated after this date (ISO 8601 format).",
 *     required=false,
 *     @OA\Schema(
 *         type="string",
 *         format="date-time",
 *         example="2021-01-01T00:00:00Z"
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="list_page",
 *     in="query",
 *     name="page",
 *     description="Page number (1000 results per page).",
 *     required=false,
 *     @OA\Schema(
 *         type="integer",
 *         example=1
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="list_bbox",
 *     in="query",
 *     name="bbox",
 *     description="Bounding box to filter elements; format [minLon, minLat, maxLon, maxLat].",
 *     required=false,
 *     @OA\Schema(
 *         type="string",
 *         example="[6.7499552751,36.619987291,18.4802470232,47.1153931748]"
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="list_score",
 *     in="query",
 *     name="score",
 *     description="Filter elements that have a score greater than or equal to the specified value.",
 *     required=false,
 *     @OA\Schema(
 *         type="integer",
 *         example=2
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="search_models",
 *     in="query",
 *     name="models",
 *     description="Optional comma-separated model list. Allowed values: admin-areas, hiking-routes, places, poles. If omitted, all available models in the current database are used.",
 *     required=false,
 *     @OA\Schema(
 *         type="string",
 *         example="admin-areas,places,poles"
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="search_lat",
 *     in="query",
 *     name="lat",
 *     description="Latitude in WGS84. Required for point-based search.",
 *     required=false,
 *     @OA\Schema(
 *         type="number",
 *         format="double",
 *         example=43.77
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="search_lon",
 *     in="query",
 *     name="lon",
 *     description="Longitude in WGS84. Required for point-based search.",
 *     required=false,
 *     @OA\Schema(
 *         type="number",
 *         format="double",
 *         example=11.25
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="search_radius",
 *     in="query",
 *     name="radius",
 *     description="Radius in meters. Use together with lat and lon.",
 *     required=false,
 *     @OA\Schema(
 *         type="number",
 *         format="double",
 *         example=1000
 *     )
 * )
 *
 * @OA\Parameter(
 *     parameter="search_bbox",
 *     in="query",
 *     name="bbox",
 *     description="Bounding box for search in format minLon,minLat,maxLon,maxLat.",
 *     required=false,
 *     @OA\Schema(
 *         type="string",
 *         example="10.49,46.17,10.50,46.18"
 *     )
 * )
 */
class BaseListParameters
{
    // This class serves as a container for common Swagger parameter annotations.
}
