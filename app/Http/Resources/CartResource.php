<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
            'article_name' => $this->article->name,
            'image' => $this->article->mediaLibraries()->first()->path,
            'unit_price' => $this->article->price,
            'raw_amount' => $this->article->price, // * $this->quantity,
            'actual_price' => $this->getActualPrice(),
            'updated_reduction_amount' => $this->getActualPrice(), // * $this->quantity,
            'quantity' => $this->quantity,
            'reduction_type' => $this->article->reduction_type,
            'reduction_price' => $this->article->reduction_price
        ];
    }
}
