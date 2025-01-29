<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActualiteResource extends JsonResource
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
            'category_id' => $this->category->id,
            'category_name' => $this->category->name,
            'name' => $this->name,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'quotation' => $this->quotation,
            'quotation_owner' => $this->quotation_owner,
            'tags' => $this->tags->pluck('name'),
            'tag' => $this->tags->pluck('name')->implode(', '),
            'medias' => MediaLibraryResource::collection($this->mediaLibraries()->where('type', 1)->get()),
            'files' => MediaLibraryResource::collection($this->mediaLibraries()->where('type', 8)->get()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
