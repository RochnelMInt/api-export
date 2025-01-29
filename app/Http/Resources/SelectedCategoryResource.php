<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SelectedCategoryResource extends JsonResource
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
            'name' => $this->category->name,
            'image' => $this->category->mediaLibraries->count() > 0 ? $this->category->mediaLibraries()->first()->path : "",
            'number_of_articles' => $this->category->articles->count(),
        ];
    }
}
