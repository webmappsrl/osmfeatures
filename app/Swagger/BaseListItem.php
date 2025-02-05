<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="BaseListItem",
 *     title="Base List Item",
 *     type="object",
 *     @OA\Property(
 *         property="current_page",
 *         type="integer",
 *         description="Current page number",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"id", "updated_at"},
 *             @OA\Property(
 *                 property="id",
 *                 type="string",
 *                 description="The Osmfeatures ID of the object",
 *                 example="R14230705"
 *             ),
 *             @OA\Property(
 *                 property="updated_at",
 *                 type="string",
 *                 format="date-time", 
 *                 description="Update date in ISO 8601 format",
 *                 example="2024-08-22T08:01:13+02:00"
 *             )
 *         )
 *     ),
 *     @OA\Property(
 *         property="first_page_url",
 *         type="string",
 *         description="URL of the first page",
 *         example="http://0.0.0.0:8006/api/v1/features/models/list?page=1"
 *     ),
 *     @OA\Property(
 *         property="from",
 *         type="integer",
 *         description="The starting index of items on current page",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="last_page",
 *         type="integer",
 *         description="Number of the last page",
 *         example=10
 *     ),
 *     @OA\Property(
 *         property="last_page_url",
 *         type="string",
 *         description="URL of the last page",
 *         example="http://0.0.0.0:8006/api/v1/features/models/list?page=10"
 *     ),
 *     @OA\Property(
 *         property="links",
 *         type="array",
 *         description="Navigation links for pagination",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="url", type="string", nullable=true),
 *             @OA\Property(property="label", type="string"),
 *             @OA\Property(property="active", type="boolean")
 *         ),
 *         example={
 *             {"url": null, "label": "pagination.previous", "active": false},
 *             {"url": "http://0.0.0.0:8006/api/v1/features/models/list?page=1", "label": "1", "active": true},
 *             {"url": "http://0.0.0.0:8006/api/v1/features/models/list?page=2", "label": "2", "active": false}
 *         }
 *     ),
 *     @OA\Property(
 *         property="next_page_url",
 *         type="string",
 *         nullable=true,
 *         description="URL of the next page",
 *         example="http://0.0.0.0:8006/api/v1/features/models/list?page=2"
 *     ),
 *     @OA\Property(
 *         property="path",
 *         type="string",
 *         description="Base path of the API endpoint",
 *         example="http://0.0.0.0:8006/api/v1/features/models/list"
 *     ),
 *     @OA\Property(
 *         property="per_page",
 *         type="integer",
 *         description="Number of items per page",
 *         example=100
 *     ),
 *     @OA\Property(
 *         property="prev_page_url",
 *         type="string",
 *         nullable=true,
 *         description="URL of the previous page",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="to",
 *         type="integer",
 *         description="The ending index of items on current page",
 *         example=100
 *     ),
 *     @OA\Property(
 *         property="total",
 *         type="integer",
 *         description="Total number of items across all pages",
 *         example=937
 *     )
 * )
 */
class BaseListItem
{
    // This schema defines common properties for items in list/pages
}
