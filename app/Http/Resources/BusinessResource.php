<?php

namespace App\Http\Resources;

class BusinessResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $this->user_id = $this->user->uuid;
        return array_merge(
            parent::toArray($request),
            [
                'user_id' =>  $this->user->uuid,
                'attributes' => BusinessAttributeResource::collection($this->attributes),
                'optional_attributes' => BusinessOptionalAttributeResource::collection($this->optionalAttributes),
                'categories' => CategoryResource::collection($this->categories),
                'reviews'    => BusinessReviewResource::collection($this->reviews),
            ]
        );
    }
}
