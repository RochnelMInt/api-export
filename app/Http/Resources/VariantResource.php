<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
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
            'price' => $this->price,
            'quantity' => $this->quantity,
            'feature_values' => FeatureValueResource::collection($this->featureValues),
            'medias' => MediaLibraryResource::collection($this->mediaLibraries),
            'reduction_type' => $this->reduction_type ? $this->reduction_type : $this->article->reduction_type,
            'reduction_price' => $this->reduction_price? $this->reduction_price : $this->article->reduction_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
