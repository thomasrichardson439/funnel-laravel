<?php

namespace App\Services\Api;

use App\Models\Business;
use App\Models\MapPreset;

class MapPresetService
{
    /**
     * @var MapPreset
     */
    private $model;

    /**
     * MapPresetService constructor.
     * @param MapPreset $mapPreset
     */
    public function __construct(MapPreset $mapPreset)
    {
        $this->model = $mapPreset;
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public function getBusinesses(array $data)
    {
        $mapPreset = $this->model->find($data['map_preset_id']);
        $businessIds = [];
        $lat = (float) $data['lat'];
        $lon = (float) $data['lon'];
        foreach ($mapPreset->categories as $category) {
            $businesses = $category->businesses()->get()->pluck('id')->toArray();
            $businessIds = array_merge($businessIds, $businesses);
        }
        return Business::search('*')->whereIn('id', $businessIds)->whereGeoDistance('location', [$lon, $lat], 10)->get();
    }
}
