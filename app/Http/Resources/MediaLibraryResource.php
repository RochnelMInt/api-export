<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaLibraryResource extends JsonResource
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
            'referral'=> $this->referral,
            'type'=> $this->type,
            'path'=> $this->path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            //->format('d/m/Y'),
        ];
    }
}
