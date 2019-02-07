<?php

namespace App\Http\Resources;

class UserResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'verified' => (bool) $this->verified,
            'age_group' => $this->age_group,
            'gender' => $this->gender,
            'cover_photo' => $this->cover_photo,
            'avatar_photo' => $this->avatar_photo,
            'bio' => $this->bio,
            'allow_location_tracking' => (bool) $this->allow_location_tracking,
            'post_publicly' => (bool) $this->post_publicly,
            't_c_agreed' => (bool) $this->t_c_agreed,
            'profile_visible' => (bool) $this->profile_visible,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'deleted_at' => $this->deleted_at ? (string) $this->deleted_at : null,
        ];
    }
}
