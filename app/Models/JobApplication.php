<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function job(){

        return $this->belongsTo(MyJob::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
