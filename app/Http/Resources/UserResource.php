<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'gender' => $this->gender,
            'about_me' => $this->about_me,
            'postal_code' => $this->postal_code,
            'is_admin' => $this->is_admin,
            'is_super_admin' => $this->is_super_admin,
            'is_first_connection' => $this->is_first_connection,
            'email' => $this->email,
        ];
    }
}
