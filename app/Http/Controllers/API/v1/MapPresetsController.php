<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MapPresets\GetMapPresetRequest;
use App\Http\Resources\BusinessCollectionResource;
use App\Http\Resources\BusinessResource;
use App\Http\Resources\MapPresetResource;
use App\Services\Api\MapPresetService;

class MapPresetsController extends Controller
{

    /**
     * @var MapPresetService
     */
    private $mapPresetService;

    /**
     * MapPresetsController constructor.
     * @param MapPresetService $mapPresetService
     */
    public function __construct(MapPresetService $mapPresetService)
    {
        $this->mapPresetService = $mapPresetService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/map-presets",
     *     summary="Get all map presets",
     *
     *   @OA\Response(response="200", description="List of MapPresetResource")
     * )
     * @param GetMapPresetRequest $request
     * @return BusinessCollectionResource
     */
    public function index(GetMapPresetRequest $request)
    {
        $results = $this->mapPresetService->getBusinesses($request->all());
        return new BusinessCollectionResource($results);
    }
}
