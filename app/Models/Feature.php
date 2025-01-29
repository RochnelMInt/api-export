<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $table = "features";

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function featureValues()
    {
        return $this->hasMany(FeatureValue::class);
    }
}
