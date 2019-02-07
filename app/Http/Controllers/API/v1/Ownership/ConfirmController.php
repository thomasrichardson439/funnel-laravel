<?php

namespace App\Http\Controllers\API\v1\Ownership;

use App\Http\Controllers\Controller;
use App\Ownership\OwnershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfirmController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/confirm-ownsership/{id}",
     * @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="token",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="id",
     *                     type="string",
     *                      description="Uuid of the category to delete",
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Ownership resource updated"),
     * )
     * @param $businessId
     * @param Request $request
     * @param OwnershipService $service
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function index($businessId, Request $request, OwnershipService $service)
    {
        $response = $service->confirmOwnership(
            Auth::id(),
            $businessId,
            $request->input('token')
        );

        return response()->json($response);
    }
}
