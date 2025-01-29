<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
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
            'purchase_uid' => $this->purchase_uid,
            'user_id' => $this->user->id,
            'user' => $this->user,
            //'quantity' => $this->quantity,
            'payment_method' => $this->payment_method,
            'is_shipped' => $this->is_shipped,
            'amount' => $this->amount,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'purchase_carts' => PurchaseCartResource::collection($this->purchaseCarts),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
