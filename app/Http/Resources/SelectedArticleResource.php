<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SelectedArticleResource extends JsonResource
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
            'article_id' => $this->article->id,
            'name' => $this->article->name,
            'price' => $this->article->price,
            'association' => $this->article->association,
            'medias' => MediaLibraryResource::collection($this->article->mediaLibraries),
        ];
    }
}
