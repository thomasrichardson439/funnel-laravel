<?php
/*
|--------------------------------------------------------------------------
| V1 API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for V1.
|
 */
Route::get('/manifest', function () {
    return [
        'servers' => [
            [
                'label' => 'v0.1',
                'url'   => 'http://104.248.44.122/api/v1',
            ],
            [
                'label' => 'v0.2',
                'url'   => 'http://167.99.193.195/api/v1',
            ],
            [
                'label' => 'Internal Testing 1',
                'url'   => 'http://104.248.253.106/api/v1',
            ],
            [
                'label' => 'Internal Testing 2',
                'url'   => 'http://167.99.93.110/api/v1',
            ],
        ],
    ];
});

Route::post('/login', 'Authentication\LoginController@store');
Route::post('/register', 'Authentication\RegistrationController@create');

Route::get('/email/verify/{id}', 'Authentication\VerificationController@verify')->name('verification.email')->middleware('signed', 'auth:api');

//use Illuminate\Http\Request;
//
//Route::get('/email/verify/{id}', function(Request $request) {
//    return $request;
//})->name('verification.email')->middleware('signed');


Route::post('/sms/verify', 'Authentication\VerificationController@verifySMS')->name('verification.sms')->middleware('auth:api');

/**
 *  Business
 */
Route::group(['middleware' => ['auth:api', 'role:admin']], function () {
    Route::get('/businesses/geo-json', 'BusinessesController@geoJson');
    Route::get('/businesses/stats', 'BusinessesController@stats');
});

Route::post('/businesses/{business}/business-cover', 'BusinessCoverController@store');

Route::group(['middleware' => ['auth:api', 'verified']], function () {
    Route::get('/logout', 'Authentication\LoginController@logout');


    Route::get('test', function() {
        return ['key' => 'value'];
    });

    /**
     * User
     */
    Route::get('users', 'UsersController@index');
    Route::post('users', 'UsersController@update');
    Route::delete('users', 'UsersController@destroy');

    //Common items
    Route::get('/common', 'CommonItemsController@index');


    /**
     * Bookmark
     */
    Route::get('/bookmark', 'BookmarkController@index');
    Route::post('/bookmark', 'BookmarkController@toggle');

    /**
     * User categories
     */
    Route::get('/user-categories', 'UserCategoriesController@index');
    Route::post('/user-categories', 'UserCategoriesController@store');

    /**
     * User-owned-businesses
     */
    Route::get('/user-owned-businesses', 'UserBusinessesController@index');

    /**
     * User businesses optional_attributes
     */
    Route::group(['prefix' => '/user-businesses/optional-attributes'], function () {
        Route::get('/', 'UserOptionalAttributesController@index');
        Route::post('/', 'UserOptionalAttributesController@store');
        Route::patch('/', 'UserOptionalAttributesController@update');
        Route::delete('/', 'UserOptionalAttributesController@delete');
    });

    /**
     * Business
     */
    Route::post('/businesses', 'BusinessesController@store');
    Route::post('/businesses/{business}', 'BusinessesController@update');
    Route::delete('/businesses/{business}', 'BusinessesController@delete');
    Route::get('/businesses/{business}', 'BusinessesController@show');
    Route::get('/business-search', 'BusinessSearchController@index');
    Route::post('/businesses/{business}/avatar', 'BusinessesController@updateAvatar');
    Route::get('/businesses/{business}/avatar/delete', 'BusinessesController@deleteAvatar');
    Route::get('/top-categories', 'TopCategoriesSearchController@search');

    Route::get('/nearby-suggest', 'NearbySuggestController@index');


    /**
     * Business Bio
     */
    Route::get('/business-bio/{id}', 'BusinessBioController@show');
    Route::patch('/business-bio', 'BusinessBioController@update');

    /**
     * Business Bio
     */
    Route::get('/business-bio/{id}', 'BusinessBioController@show');
    Route::patch('/business-bio', 'BusinessBioController@update');

    /**
     * Business Posts
     */
    Route::resource('/business-posts', 'BusinessPostsController')->only([
        'show', 'store', 'update', 'destroy'
    ]);

    Route::get('/business-posts/business/{id}', 'BusinessPostsController@forBusiness');

    Route::get('/active-business-posts', 'ActiveBusinessPostsController@index');

    Route::put('/business-hours/{business}', 'BusinessHoursController@update');
    Route::delete('/business-hours/{business}', 'BusinessHoursController@delete');

    /**
     * Business Reviews
     */
    Route::post('/business-reviews', 'BusinessReviewsController@store');
    Route::get('/user-business-reviews/{userId}', 'UserReviewsController@index');

    /**
     * Business Feed
     */
    Route::get('/business-feed/{businessId}', 'BusinessFeedController@index');

    /**
     * User feed
     */
    Route::get('/user-feed', 'UserFeedController@index');

    /**
     * User feed
     */
    Route::get('/user-home-feed', 'UserFeedController@homeFeed');

    /**
     * Images
     */
    Route::any('/face-detection', 'FaceDetectionController@index');

    /**
     * Explore
     */
    Route::get('/explore', 'ExploreController@index');

    /**
     *  Discover
     */
    Route::get('/discover', 'DiscoverController@index');

    /**
     *  Map Presets
     */
    Route::get('/map-presets', 'MapPresetsController@index');

    /**
     * Stickers
     */
    Route::get('/sticker-categories', 'StickerCategoriesController@index');
    Route::get('/stickers', 'StickersController@index');

    /**
     * Categories
     */
    Route::get('/categories', 'CategoriesController@index');

    /**
     * Ownership
     */
    Route::get('/ownership-methods/{businessId}', 'Ownership\MethodsController@index');
    Route::post('/ownership-requests/{businessId}', 'Ownership\RequestsController@store');
    Route::get('/ownership-requests/{businessId}', 'Ownership\RequestsController@index');
    Route::post('/confirm-ownership/{businessId}', 'Ownership\ConfirmController@index');

    /**
     * Feed
     */
    Route::get('/feed', 'FeedController@index');

    /**
     * Logged in user data
     */
    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });
});
