<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $casts = [
        'keywords' => 'array'
    ];

    public function category(){

        return $this->belongsTo(Category::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function variants(){

        return $this->hasMany(Variant::class);
    }

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getActualPrice()
    {
        $actualPrice = 0;

        if ($this->reduction_type === "AMOUNT") {
            $actualPrice = $this->price - $this->reduction_price;
        } elseif ($this->reduction_type === "PERCENTAGE") {
            $discount = $this->price * ($this->reduction_price / 100);
            $actualPrice = $this->price - $discount;
        } else {
            $actualPrice = $this->price;
        }

        return $actualPrice;
    }
}
