<?php

namespace App\Services\Api;

use App\Models\Business;
use App\Utils\GeoLocation;
use App\Utils\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BusinessService
{
    /**
     * @var Business
     */
    private $model;

    /**
     * @var GeoLocation
     */
    private $geoLocation;


    /**
     * @var ImageUploadService
     */
    private $imageUploadService;

    /**
     * @var CategoryService
     */
    private $categoryService;


    /**
     * BusinessService constructor.
     * @param Business $model
     * @param GeoLocation $geoLocation
     * @param ImageUploadService $imageUploadService
     * @param CategoryService $categoryService
     */
    public function __construct(Business $model,
                                GeoLocation $geoLocation,
                                ImageUploadService $imageUploadService,
                                CategoryService $categoryService)
    {
        $this->model = $model;
        $this->geoLocation = $geoLocation;
        $this->imageUploadService = $imageUploadService;
        $this->categoryService = $categoryService;
    }

    /**
     * @param $businessUuid
     * @return mixed
     */
    public function get($businessUuid)
    {
        return $this->model->uuid($businessUuid);
    }

    /**
     * @param $lat
     * @param $lon
     * @param $radius
     * @param int $howMany
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNearby($lat, $lon, $radius, $howMany = 50)
    {
        $boundingBox  = $this->geoLocation->getElasticSearchBounding($lat, $lon, $radius);
        return $this->model->search('*')->whereGeoBoundingBox('location', $boundingBox)->take($howMany)->get();
    }

    /**
     * @param int $howMany
     * @return mixed
     */
    public function getUserOwnedBusinesses($howMany = 50)
    {
        return $this->model->where('user_id', auth()->id())->paginate($howMany);
    }

    /**
     * @param $data
     * @return Business
     * @throws \App\Exceptions\GeneralException
     */
    public function create($data)
    {
        $categoryId = $data['category_id'];

        unset($data['category_id']);

        DB::beginTransaction();

        if (!empty($data['avatar'])) {
            $data['avatar'] = $this->processImage($data, 'avatar');
        }
        if (!empty($data['cover_photo'])) {
            $data['cover_photo'] = $this->processImage($data, 'cover_photo');
        }
        $business = $this->model->create($data);

        if($categoryId) {
            $cat = $this->categoryService->get($categoryId);
            $business->categories()->attach($cat->id, [
                'relevance' => 100
            ]);
        }

        DB::commit();
        return $business;
    }

    public function update($businessUuid, array $data)
    {
        $business = $this->get($businessUuid);
        $categoryId = $data['category_id'];
        unset($data['category_id']);

        DB::beginTransaction();

        if (!empty($data['avatar'])) {
            $data['avatar'] = $this->processImage($data, 'avatar');
        }
        if (!empty($data['cover_photo'])) {
            $data['cover_photo'] = $this->processImage($data, 'cover_photo');
        }
        $business = $business->fill($data);
        $business->update($data);

        if($categoryId) {
            $cat = $this->categoryService->get($categoryId);
            $business->categories()->sync($cat->id, [
                'relevance' => 100
            ]);
        }

        DB::commit();
        return $business;
    }


    /**
     * Update a Business cover
     * @param Business $business
     * @param $validatedRequest
     * @return Business
     * @throws \App\Exceptions\GeneralException
     */
    public function updateCover(Business $business, $validatedRequest)
    {
        if (!empty($validatedRequest['cover_photo'])) {
            $validatedRequest['cover_photo'] = $this->processImage($validatedRequest, 'cover_photo');
        }

        $business->fill($validatedRequest)->save();
        return $business;
    }

    /**
     * @param $data
     * @param $key
     * @return string|null
     * @throws \App\Exceptions\GeneralException
     */
    private function processImage($data, $key)
    {
        if (empty($data[$key])) {
            return null;
        }
        if ($data[$key] instanceof UploadedFile) {
            return $this->imageUploadService->saveImage($data[$key], 'images');
        }
        return null;
    }

}
