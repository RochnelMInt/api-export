<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseCartResource extends JsonResource
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
            'cart_id' => $this->cart->id,
            'article_name' => $this->cart->article->name,
            'article_type' => $this->cart->article->type,
            'image' => $this->cart->article->mediaLibraries()->first()->path,
            'amount' => $this->cart->amount,
            //'quantity' => $this->cart->quantity,
            'unit_price' => $this->cart->article->price,
            'raw_amount' => $this->cart->article->price, // * $this->quantity,
            'actual_price' => $this->cart->getActualPrice(),
            'updated_reduction_amount' => $this->cart->getActualPrice(), // * $this->cart->quantity,
            'reduction_type' => $this->cart->article->reduction_type,
            'reduction_price' => $this->cart->article->reduction_price
        ];
    }
}
