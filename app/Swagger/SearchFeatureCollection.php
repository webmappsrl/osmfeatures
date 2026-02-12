<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="SearchFeatureCollection",
 *     title="Search Feature Collection",
 *     type="object",
 *     required={"type", "features"},
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         example="FeatureCollection"
 *     ),
 *     @OA\Property(
 *         property="features",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(
 *                 property="type",
 *                 type="string",
 *                 example="Feature"
 *             ),
 *             @OA\Property(
 *                 property="properties",
 *                 type="object",
 *                 @OA\Property(
 *                     property="model",
 *                     type="string",
 *                     example="places"
 *                 ),
 *                 @OA\Property(
 *                     property="osmfeatures_id",
 *                     type="string",
 *                     example="N123456"
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="geometry",
 *                 type="object",
 *                 @OA\Property(
 *                     property="type",
 *                     type="string",
 *                     example="Point"
 *                 ),
 *                 @OA\Property(
 *                     property="coordinates",
 *                     type="array",
 *                     @OA\Items(type="number")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
class SearchFeatureCollection
{
    // Swagger schema container.
}
