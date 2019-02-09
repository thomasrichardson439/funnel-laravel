<?php
/**
 * Created by PhpStorm.
 * User: byabuzyak
 * Date: 11/21/18
 * Time: 12:46 PM
 */

namespace App\Repositories;

use App\Elastic\Entities\Feed;
use App\Elastic\Rules\FeedRule;
use App\Models\Business;
use App\Models\BusinessPost;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FeedRepository
{
    /**
     * @var Feed
     */
    protected $model;

    /**
     * @var User
     */
    protected $userModel;

    /**
     * @var int
     */
    public $size = 10;

    /**
     * FeedRepository constructor.
     * @param Feed $feed
     * @param User $userModel
     */
    public function __construct(Feed $feed, User $userModel)
    {
        $this->model = $feed;
        $this->userModel = $userModel;
    }

    /**
     * @param $lat
     * @param $lng
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get($lat, $lng) {
        return
            $this
                ->model
                ->rule(FeedRule::build($lat, $lng))
                ->setHitSource('top')
                ->paginate();
    }

    /**
     * @param $businessId
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function forBusiness($businessId)
    {
        return
            $this
                ->model
                ->query(FeedRule::business($businessId))
                ->paginate()
            ;
    }

    /**
     * @return mixed
     */
    public function forUser()
    {
        $user       = Auth::user();
        $reviews    = $user->reviews()->paginate($this->size);
        $businesses = $user->businesses()->paginate($this->size);
        $results    = $reviews->merge($businesses)->shuffle()->makeHidden(['user_id']);

        return
            new LengthAwarePaginator(
                $results,
                $results->count(),
                $this->size,
                Paginator::resolveCurrentPage('page'),
                [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );
    }
    /**
     * @return mixed
     */
    public function userOwnedBusinesses()
    {
        $user       = Auth::user();
        $results    = $user->businesses()->paginate($this->size);

        return
            new LengthAwarePaginator(
                $results,
                $results->count(),
                $this->size,
                Paginator::resolveCurrentPage('page'),
                [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );
    }


    /**
     * @return mixed
     */
    public function forHomeFeed(?float $lat, ?float $lng, ?string $distance)
    {
        $businessId = Business::search("*")
            ->whereGeoDistance("location", [$lng, $lat], $distance)->orderBy('score', 'desc')->get()->pluck('id')->toArray();
        // stash the query in a variable since we'll be reusing it in two separate places
        $getQuery = function ($id) {
            return BusinessPost::where('business_id', '=', $id)
                ->join('businesses', 'businesses.id', '=', 'business_posts.business_id')
                ->join('users', 'users.id', '=', 'business_posts.user_id')
                ->leftJoin('business_post_images', function ($join) {
                    $join->on('business_posts.id', '=', 'business_post_images.business_post_id');
                })
                ->addSelect(
                    'business_posts.*',
                    'businesses.name as business_name',
                    'businesses.uuid as business_uuid',
                    'users.cover_photo as user_avatar',
                    'users.name as user_name'
                );
        };

        $firstId = array_shift($businessId);
        $query = $getQuery($firstId);

        foreach ($businessId as $id) {
            $query->union($getQuery($id));
        }
        // seeding the inRandomOrder call removes need to cache the result as we roll the dice the same way each time
        $results = $query->inRandomOrder(1788)->paginate(10);


        // post-process paginator to bolt on URL fields
        $data = $results->getCollection();

        $dataTemp = $data->map(function ($var) {
            if (null !== $var['user_avatar']) {
                $var['user_avatar'] = Storage::disk('remote')->url($var['user_avatar']);
            }
            if (0 == count($var['images'])) {
                $var['photo_url'] = null;
            } else {
                $var['photo_url'] = $var['images'][0]['url'];
            }
            $var['images'] = [];
            return $var;
        }, $data);

        $results->setCollection($dataTemp);

        return $results;
    }
}
