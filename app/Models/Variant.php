<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    public function article(){

        return $this->belongsTo(Article::class);
    }

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function featureValues()
    {
        return $this->belongsToMany(FeatureValue::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
}
