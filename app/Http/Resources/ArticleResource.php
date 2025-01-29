<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
            'type' => $this->type,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'price' => $this->price,
            'actual_price' => $this->getActualPrice(),
            'reduction_price' => $this->reduction_price,
            'reduction_type' => $this->reduction_type,
            'path' => $this->path,
            'preview_path' => $this->preview_path,
            'medias' => MediaLibraryResource::collection($this->mediaLibraries),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
