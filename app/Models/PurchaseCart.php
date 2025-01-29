<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseCart extends Model
{
    use HasFactory;

    public function purchase(){
        return $this->belongsTo(Purchase::class);
    }

    public function cart(){
        return $this->belongsTo(Cart::class);
    }
}
