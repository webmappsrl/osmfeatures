<?php

namespace App\Http\Controllers;

use App\Services\FeatureSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class SearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/search",
     *     operationId="searchFeatures",
     *     tags={"API V1"},
     *     summary="Search features by spatial query",
     *     description="Search features by model using point, point+radius or bbox. If models is not provided, the API searches across all models currently available in the database. Results are capped to 50 features per model.",
     *     @OA\Parameter(ref="#/components/parameters/search_models"),
     *     @OA\Parameter(ref="#/components/parameters/search_lat"),
     *     @OA\Parameter(ref="#/components/parameters/search_lon"),
     *     @OA\Parameter(ref="#/components/parameters/search_radius"),
     *     @OA\Parameter(ref="#/components/parameters/search_bbox"),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/SearchFeatureCollection")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function search(Request $request, FeatureSearchService $searchService): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'models' => 'nullable|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lon' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1',
            'bbox' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        [$mode, $filters, $modeErrors] = $this->resolveSpatialMode($request);
        if ($modeErrors !== []) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $modeErrors,
            ], 422);
        }

        try {
            $requestedModels = $searchService->resolveModels($request->query('models'));
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => ['models' => [$exception->getMessage()]],
            ], 422);
        }

        $availableModels = $searchService->modelsWithExistingTables($requestedModels);
        $searchableModels = $searchService->modelsSupportingMode($availableModels, $mode);

        if ($request->filled('models')) {
            $unsupportedModels = array_values(array_diff($availableModels, $searchableModels));
            if ($unsupportedModels !== []) {
                return response()->json([
                    'message' => 'Validation errors',
                    'errors' => [
                        'models' => [
                            'Selected mode "' . $mode . '" is not supported by: ' . implode(', ', $unsupportedModels),
                        ],
                    ],
                ], 422);
            }
        }

        $features = $searchableModels === []
            ? []
            : $searchService->search($searchableModels, $mode, $filters);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, array<int, string>>}
     */
    private function resolveSpatialMode(Request $request): array
    {
        $hasBbox = $request->filled('bbox');
        $hasLat = $request->filled('lat');
        $hasLon = $request->filled('lon');
        $hasRadius = $request->filled('radius');
        $errors = [];

        if ($hasBbox && ($hasLat || $hasLon || $hasRadius)) {
            $errors['geometry'] = ['Use either bbox or lat/lon/radius parameters, not both'];
            return ['', [], $errors];
        }

        if ($hasLat xor $hasLon) {
            $errors['geometry'] = ['Both lat and lon are required when using point-based search'];
            return ['', [], $errors];
        }

        if ($hasRadius && !($hasLat && $hasLon)) {
            $errors['radius'] = ['Radius requires both lat and lon'];
            return ['', [], $errors];
        }

        if ($hasBbox) {
            [$bbox, $bboxError] = $this->parseBbox((string) $request->query('bbox'));
            if ($bboxError !== null) {
                $errors['bbox'] = [$bboxError];
                return ['', [], $errors];
            }

            return ['bbox', ['bbox' => $bbox], []];
        }

        if ($hasLat && $hasLon && $hasRadius) {
            return ['radius', [
                'lat' => (float) $request->query('lat'),
                'lon' => (float) $request->query('lon'),
                'radius' => (float) $request->query('radius'),
            ], []];
        }

        if ($hasLat && $hasLon) {
            return ['point', [
                'lat' => (float) $request->query('lat'),
                'lon' => (float) $request->query('lon'),
            ], []];
        }

        $errors['geometry'] = ['Provide either bbox or lat/lon (+ optional radius)'];
        return ['', [], $errors];
    }

    /**
     * @return array{0: ?array{minLon: float, minLat: float, maxLon: float, maxLat: float}, 1: ?string}
     */
    private function parseBbox(string $bbox): array
    {
        $parts = array_map('trim', explode(',', $bbox));
        if (count($parts) !== 4) {
            return [null, 'Bounding box must contain exactly four comma-separated values'];
        }

        if (array_filter($parts, static fn(string $value): bool => !is_numeric($value)) !== []) {
            return [null, 'Bounding box values must be numeric'];
        }

        $minLon = (float) $parts[0];
        $minLat = (float) $parts[1];
        $maxLon = (float) $parts[2];
        $maxLat = (float) $parts[3];

        if ($minLon < -180 || $maxLon > 180 || $minLat < -90 || $maxLat > 90) {
            return [null, 'Bounding box coordinates are out of WGS84 bounds'];
        }

        if ($minLon >= $maxLon || $minLat >= $maxLat) {
            return [null, 'Bounding box min values must be lower than max values'];
        }

        return [[
            'minLon' => $minLon,
            'minLat' => $minLat,
            'maxLon' => $maxLon,
            'maxLat' => $maxLat,
        ], null];
    }
}
