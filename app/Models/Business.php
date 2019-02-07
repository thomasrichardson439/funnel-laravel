<?php

namespace App\Models;

use App\Models\Traits\HasOpenableHours;
use App\Models\Traits\WithRelationsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use ScoutElastic\Searchable;
use App\Traits\HasUuid;
use Illuminate\Support\Str;

class Business extends Model
{
    use Searchable, HasUuid, WithRelationsTrait, HasOpenableHours;

    protected $guarded = [];
    protected $hidden   = ['internal_score',  'avatar', 'cover_photo'];
    protected $appends  = ['cover_photo_url', 'avatar_photo_url'];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * @var \App\Elastic\Configurators\Business
     */
    protected $indexConfigurator = \App\Elastic\Configurators\Business::class;

    /**
     * @var array
     */
    protected $searchRules = [
        \App\Elastic\Rules\BusinessSearchRule::class
    ];


    /**
     * On boot actions, attach default behaviour
     */
    protected static function boot() {

        parent::boot();

        static::deleting(function ($business) {
            $business->categories()->sync([]);
        });

    }

    /**
     * ElasticSearch search rules boosting
     */
    const boostOpened    = 5.5;
    const boostNameMatch = 1.0;
    const fuzziness      = 5;
    const OPEN_HOUR = 'open';
    const BUSINESS_HOUR = 'business';

    /**
     * @var array
     */
    protected $mapping = [
        'properties' => [
            'id'                => [
                'type'  => 'integer',
                'index' => 'true'
            ],
            'name'              => [
                'type'   => 'text',
                'fields' => [
                    'english' => [
                        'type'     => 'text',
                        'analyzer' => 'english'
                    ],
                    'synonym' => [
                        'type'     => 'text',
                        'analyzer' => 'synonym_analyzer',
                        'index'    => 'true'
                    ],
                ]
            ],
            'suggest' => [
                'type' => 'completion'
            ],
            'exact_name'        => [
                'type'     => 'text',
                'analyzer' => 'substring_analyzer',
                'index'    => 'true'
            ],
            'categories'        => [
                'type'       => 'nested',
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'index' => 'false'
                    ],
                    'name' => [
                        'type'     => 'text',
                        'index'    => 'true',
                        'analyzer' => 'whitespace_analyzer'
                    ],
                ]
            ],
            'location'          => [
                'type'  => 'geo_point',
                'index' => 'true'
            ],
            'total_reviews'     => [
                'type' => 'long'
            ],
            'score'             => [
                'type'  => 'integer',
                'index' => 'true'
            ],
            'internal_score'    => [
                'type' => 'integer'
            ],
            'total_posts'      => [
                'type' => 'long'
            ],
            'hours'          => [
                'type'       => 'nested',
                'properties' => [
                    'day_of_week'       => [
                        'type'  => 'byte',
                        'index' => 'true'
                    ],
                    'open_period_mins'  => [
                        'type'  => 'short',
                        'index' => 'true'
                    ],
                    'close_period_mins' => [
                        'type'  => 'short',
                        'index' => 'true'
                    ]
                ]
            ],
            'cover_photo_url'   => [
                'type'  => 'text',
                'index' => 'false'
            ],
            'avatar' => [
                'type' => 'text',
                'index' => 'false'
            ]
        ]
    ];

    /**
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'name'            => $this->name,
            'suggest'         => $this->name,
            'exact_name'      => $this->name,
            'location'        => [
                'lat' => $this->lat,
                'lon' => $this->lng
            ],
            'total_reviews'   => $this->reviews_count,
            'total_posts'     => $this->posts_count,
            'categories'      => $this->categories,
            'optional_attributes' => $this->optionalAttributes,
            'internal_score'  => $this->internal_score,
            'hours'           => $this->businessHours,
            'cover_photo_url' => $this->cover_photo_url
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withRequiredForUpdate()
    {
        return (new static)->newQuery()
            ->with('categories')
            ->withCount('reviews', 'posts');
    }

    public function businessHours()
    {
        return $this->hasMany(BusinessHour::class)->where('hour_type', '=', self::BUSINESS_HOUR);
    }

    /**
     * @return int
     */
    public static function currentMinutes(): int
    {
        return intval(ceil((strtotime(now()) - strtotime("12:00am")) / 60));
    }

    /**
     * @return int
     */
    public function getTotalReviewsAttribute()
    {
        return $this->reviews->count();
    }

    /**
     * @return int
     */
    public function getTotalAttributesAttribute()
    {
        return isset($this->relations['attributes']) ? count($this->relations['attributes']) : 0;
    }

    /**
     * @return int
     */
    public function getTotalEmailAttributesAttribute()
    {
        return isset($this->relations['totalEmailAttributes']) ? count($this->relations['totalEmailAttributes']) : 0;
    }

    /**
     * @return array
     */
    public function getLocationAttribute()
    {
        return [$this->lng, $this->lat];
    }

    /**
     * @return void
     */
    private function updateScore()
    {
        $countReviews = $this->reviews_count;
        $avgReview    = $this->reviews_avg_code;

        if($countReviews  === 0) {
            $score = 80;
        } else if($countReviews < 5) {
            $score = ($avgReview / 5 * 0.3 + 0.5) * 100;
        } else {
            $score = $avgReview / 5 * 100;
        }

        $this->score = $score;
    }

    /**
     * @return void
     */
    private function updateInternalScore()
    {
        $score = 0;

        if ($this->reviews_exists) {
            $score += 20;
        }
        if ($this->posts_exists) {
            $score += 20;
        }
        if ($this->categories_exists) {
            $score += 20;
        }
        if ($this->addyAttributes_exists) {
            $score += 20;
        }
        if ($this->attributes_count > 1) {
            $score += 20;
        }

        $this->internal_score = $score;
    }

    public function updateScores() {
        $this->updateInternalScore();
        $this->updateScore();
        $this->save();
    }

    /**
     * @return string|null
     */
    public function getAvatarPhotoUrlAttribute()
    {
        return $this->avatar ? Storage::disk('public_new')->url($this->avatar) : null;
    }

    /**
     * @return string|null
     */
    public function getCoverPhotoUrlAttribute()
    {
        return $this->cover_photo ? Storage::disk('public_new')->url($this->cover_photo) : null;
    }

    public function getTotalPostsAttribute()
    {
        return $this->posts->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return
            $this
                ->belongsToMany(Category::class, 'business_category')
                ->withPivot(['relevance'])
                ->withTimestamps()
                ->orderBy('relevance', 'DESC')
            ;
    }

    public function categoriesExists()
    {
        return $this->hasManyExists(BusinessCategory::class);
    }

    public function getCategoriesExistsAttribute()
    {
        return $this->getExistsAttributeValue('categoriesExists');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function keywords()
    {
        return $this->hasMany(BusinessKeyword::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributes()
    {
        return $this->hasMany(BusinessAttribute::class);
    }

    public function getAttributesCountAttribute($count)
    {
        if ($count === null) {
            $count = $this->attributes()->count();
        }

        return $count;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function totalEmailAttributes()
    {
        return
            $this->hasMany(BusinessAttribute::class)->where('key', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addyAttributes()
    {
        return $this->hasMany(BusinessAttribute::class)->where('key', 'addy');
    }

    public function addyAttributesExists()
    {
        return $this->hasManyExists(BusinessAttribute::class)->where('key', 'addy');
    }

    public function getAddyAttributesExistsAttribute()
    {
        return $this->getExistsAttributeValue('addyAttributesExists');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return
            $this
                ->hasMany(BusinessReview::class)
                ->select(['*', DB::raw("IF(`comment` > '', 1, 0) `order`")])
                ->orderBy('order', 'DESC')
                ->orderBy('created_at', 'DESC');
    }

    public function getReviewsCountAttribute($count)
    {
        if ($count === null) {
            $count = $this->reviews()->count();
        }

        return $count;
    }

    public function reviewsAvgCode()
    {
        return $this->hasManyAvg('code', BusinessReview::class);
    }

    public function getReviewsAvgCodeAttribute()
    {
        return $this->getAvgAttributeValue('reviewsAvgCode');
    }

    public function reviewsExists()
    {
        return $this->hasManyExists(BusinessReview::class);
    }

    public function getReviewsExistsAttribute()
    {
        return $this->getExistsAttributeValue('reviewsExists');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function postImages()
    {
        return $this->hasMany(BusinessPost::class)->with('images.labels');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function images()
    {
        return $this->hasManyThrough(BusinessPostImage::class, BusinessPost::class);
    }

    /**
     * @param $data
     * @return Model
     */
    public function createReview($data)
    {
        return $this->reviews()->create($data);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts() {
        return $this->hasMany(BusinessPost::class);
    }

    public function getPostsCountAttribute($count)
    {
        if ($count === null) {
            $count = $this->posts()->count();
        }

        return $count;
    }

    public function postsExists()
    {
        return $this->hasManyExists(BusinessPost::class);
    }

    public function getPostsExistsAttribute()
    {
        return $this->getExistsAttributeValue('postsExists');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users() {
        return $this
                ->belongsToMany(User::class);
    }

    public function user() {
        return $this
            ->belongsTo(User::class);
    }


        /**
     * return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function optionalAttributes() {
        return
            $this
                ->belongsToMany(OptionalAttribute::class)
                ->withPivot(['description'])
                ->withTimestamps()
            ;
    }



    /**
     * Duplicate
     * @param $value
     */
    public function setOpenPeriodMinsAttribute($value) {
        $this->attributes['open_period_mins'] = Business::minutesCnt($value);
    }

    /**
     * Duplicate
     * @param $value
     */
    public function setClosePeriodMinsAttribute($value) {
        $this->attributes['close_period_mins'] = Business::minutesCnt($value);
    }
}
