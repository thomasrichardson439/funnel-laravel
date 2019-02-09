<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Requests\Api\BusinessReviews\StoreBusinessReview;
use App\Models\Business;
use App\Models\BusinessReview;
use App\Services\Api\BusinessReviewService;
use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessReviewResource;
use Illuminate\Http\Request;

class BusinessReviewsController extends Controller
{
    /**
     * @var BusinessReviewService
     */
    private $businessReviewService;

    /**
     * BusinessReviewController constructor.
     * @param BusinessReviewService $businessReviewService
     */
    public function __construct(BusinessReviewService $businessReviewService)
    {
        $this->businessReviewService = $businessReviewService;
        $this->authorizeResource(BusinessReview::class, 'post');
    }

    /**
     *  @OA\Post(
     *     path="/api/v1/business-reviews/{business}",
     *     summary="Create a business review",
     *   @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="business_id",
     *                     description="id of business",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="score",
     *                     description="Score",
     *                     type="int"
     *                 ),
     *                 @OA\Property(
     *                     property="comment",
     *                     description="Review comment",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="photo",
     *                     description="image",
     *                     type="file"
     *                 ),
     *                 @OA\Property(
     *                     property="mode",
     *                     description="Public or private entry.",
     *                     type="boolean"
     *                 ),
     *         ),
     *     ),
     * ),
     *     @OA\Response(response="201", description="BusinessPostResource"),
     *  )
     */
    public function store(StoreBusinessReview $request, Business $business) {
        $this->authorize('create', BusinessReview::class);

        return $this->sendResponse(
            new BusinessReviewResource($this->businessReviewService->create($business, $request->validated()))
        );

    }

}
