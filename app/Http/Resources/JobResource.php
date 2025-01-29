<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
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
            'title' => $this->title,
            'domain' => $this->domain,
            'type' => $this->type,
            'description' => $this->description,
            'salary_start' => $this->salary_start,
            'salary_end' => $this->salary_end,
            'end_date' => $this->end_date,
            'start_date' => $this->start_date,
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'qualifications' => $this->qualifications,
            'expectations' => $this->expectations,
            'benefits' => $this->benefits,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
