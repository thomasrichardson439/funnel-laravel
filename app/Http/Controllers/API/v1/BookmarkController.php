<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Bookmark;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class BookmarkController extends Controller
{
    /**
     * @OA\GET(
     *     path="/api/v1/bookmark",
     *     summary="Get all user's bookmarks",
     *     @OA\Response(response="200", description="Bookmarks Listing"),
     *
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $businesses = Bookmark::with('business')->where('user_id',$request->user()->id)->get();
        return $this->sendResponse($businesses, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Bookmark  $bookmark
     * @return \Illuminate\Http\Response
     */
    public function show(Bookmark $bookmark)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Bookmark  $bookmark
     * @return \Illuminate\Http\Response
     */
    public function edit(Bookmark $bookmark)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Bookmark  $bookmark
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Bookmark $bookmark)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Bookmark  $bookmark
     * @return \Illuminate\Http\Response
     */
    public function destroy(Bookmark $bookmark)
    {
        //
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string'
        ]);

        $business = Business::where('uuid',$request->uuid)->firstOrFail();

        $bookmark = Bookmark::where('business_id',$business->id)->where('user_id',$request->user()->id)->first();


        if($bookmark){
            $bookmark->delete();
            return
                $this->sendResponse([
                    'message' => 'Bookmark successfully deleted!',
                ], 200);
        }

        $bookmark = new Bookmark;
        $bookmark->user_id = $request->user()->id;
        $bookmark->business_id = $business->id;
        $bookmark->save();

        return
            $this->sendResponse([
                'message' => 'Bookmark successfully created!',
            ], 200);
    }
}
