<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    public function user(){

        return $this->belongsTo(User::class);
    }

    public function article(){

        return $this->belongsTo(Article::class);
    }

    public function getActualPrice()
    {
        $actualPrice = 0;
        $article = $this->article;

        if ($article->reduction_type === "AMOUNT") {
            $actualPrice = $article->price - $article->reduction_price;
        } elseif ($article->reduction_type === "PERCENTAGE") {
            $discount = $article->price * ($article->reduction_price / 100);
            $actualPrice = $article->price - $discount;
        } else {
            $actualPrice = $article->price;
        }

        return $actualPrice;
    }

}
