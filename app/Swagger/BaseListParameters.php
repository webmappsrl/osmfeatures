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
 *     description="Page number (100 results per page).",
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
 */
class BaseListParameters
{
    // This class serves as a container for common Swagger parameter annotations.
}
