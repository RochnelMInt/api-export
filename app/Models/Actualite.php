<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actualite extends Model
{
    use HasFactory;

    public function category(){

        return $this->belongsTo(Category::class);
    }

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
