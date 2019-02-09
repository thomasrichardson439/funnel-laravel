<?php

namespace App\Http\Controllers\API\v1;

use App\Elastic\Rules\AggregationRule;
use App\Elastic\Rules\AttributesCountRule;
use App\Http\Requests\Api\Businesses\BookmarkBusiness;
use App\Http\Requests\Api\Businesses\StoreBusiness;
use App\Http\Requests\Api\Businesses\UpdateBusiness;
use App\Models\Business;
use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Rules\LatLng;
use App\Services\Api\BusinessService;
use Elasticsearch\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessesController extends Controller
{
    /**
     * @var BusinessService
     */
    private $businessService;

    /**
     * BusinessesController constructor.
     * @param BusinessService $businessService
     */
    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/businesses/geo-json",
     *     summary="Get GEO Json for businesses",
     *     @OA\Response(response="200", description="Geo Data JSON filestream"),
     *  )
     * @return mixed
     */
    public function geoJson() {
        $fileToDownload = last(explode("/", config('filesystems.geojson_path')));
        return Storage::download($fileToDownload);
    }

    /**
     *  @OA\Get(
     *     path="/api/v1/businesses/stats",
     *     summary="Get business stats",
     *     @OA\Parameter(
     *         name="top_left",
     *         in="query",
     *         description="Top Left of location (GPS)",
     *         required=true,
     *         @OA\Schema(
     *             type="float"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="bottom_right",
     *         in="query",
     *         description="Bottom right of location (GPS)",
     *         required=true,
     *         @OA\Schema(
     *             type="float"
     *         )
     *     ),
     *     @OA\Response(response="200", description="Stats data"),
     *  )
     * @param Request $request
     * @param Client $elasticClient
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function stats(Request $request, Client $elasticClient) {
        $this->validate($request, [
            'top_left'     => ['required', new LatLng],
            'bottom_right' => ['required', new LatLng]
        ]);

        $topLeft     = $request->get('top_left');
        $bottomRight = $request->get('bottom_right');

        // TODO: should there be a similar check for lng values?
        // TODO: what happens (in extreme case) when search box is around where the Equator crosses the International Date Line?
        if ($topLeft['lat'] <= $bottomRight['lat']) {
            return response()->json([
                'message' => 'The given data is invalid'
            ], 422);
        }

        $response = $elasticClient->search(AggregationRule::buildRule($topLeft, $bottomRight));
        $response = $response['aggregations'];

        $attributes = $elasticClient->search(AttributesCountRule::buildRule($topLeft, $bottomRight));

        return response()->json([
            'totalBusinesses' => $response['total_businesses']['value'],
            'totalImages'     => $response['total_images']['value'],
            'totalReviews'    => $response['total_reviews']['value'],
            'attributes'      => view('partials.attributes', ['attributes' => $attributes['aggregations']])->render()
        ]);
    }

    /**
     *  @OA\Get(
     *     path="/api/v1/businesses/{id}",
     *     summary="Get a single business by ID",
     *   @OA\Response(response="200", description="BusinessResource information")
     *  )
     * @param $id
     * @return BusinessResource
     */
    public function show($id)
    {
        $business = Business::uuid($id);

        return new BusinessResource($business);
    }



    /**
     * @OA\Post(
     *     path="/api/v1/businesses",
     *     summary="Create a business",
     *  @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     description="Name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lat",
     *                     description="latitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lng",
     *                     description="longitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bio",
     *                     description="business bio",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     description="Avatar Image File",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     description="Category UUID",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="cover_photo",
     *                     description="Cover Photo Image File",
     *                     type="string"
     *                 ),
     *             )
     *         ),
     *     ),
     *   @OA\Response(response="200", description="BusinessResource"),
     *  )
     * @param StoreBusiness $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\GeneralException
     */
    public function store(StoreBusiness $request)
    {
        $business = $this->businessService->create($request->validated());
        return $this->sendResponse(new BusinessResource($business), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/businesses/{business}",
     *     summary="Update a business based on UUID passed.",
     *  @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     description="base 64 encoded avatar inage",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lat",
     *                     description="latitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="lng",
     *                     description="longitude",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="id",
     *                     description="business uuid",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="category_id",
     *                     description="Category Id",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="bio",
     *                     description="business bio",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     description="business avatar",
     *                     type="file"
     *                 ),
     *                 @OA\Property(
     *                     property="cover_photo",
     *                     description="business cover photo",
     *                     type="file"
     *                 ),
     *             )
     *         ),
     *     ),
     *   @OA\Response(response="200", description="Business updated"),
     *   @OA\Response(response="400", description="Business not found"),
     *  )
     * @param UpdateBusiness $request
     * @param $businessUuid
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Business $business, UpdateBusiness $request)
    {
        $this->authorize('update', $business);

        $business = $this->businessService->update($business, $request->validated());

        return $this->sendResponse(new BusinessResource($business), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/businesses/{businessId}",
     *     summary="Delete a business by ID",
     *  @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="id",
     *                     description="business uuid",
     *                     type="string"
     *                 ),
     *             )
     *         ),
     *     ),
     *   @OA\Response(response="200", description="Business updated"),
     *   @OA\Response(response="400", description="Business not found"),
     *  )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(Request $request, Business $business)
    {
        $this->authorize('delete', $business);

        $business->delete();

        return $this->sendResponse(['message' => 'Resource deleted successfully'], 200);
    }

    /**
     * @OA\POST(
     *     path="/api/v1/bookmark",
     *     summary="Toggle bookmark specified by ID for logged in user",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="uuid",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Bookmark successfully created/deleted!"),
     *
     * )
     * @param BookmarkBusiness $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBookmark(BookmarkBusiness $request)
    {
        $result = $this->businessService->bookmark($request->get('uuid'));
        if ($result == false) {
            return $this->sendResponse([
                'message' => 'Bookmark successfully deleted!',
            ], 200);
        }
        return
            $this->sendResponse([
                'message' => 'Bookmark successfully created!',
            ], 200);
    }

}
