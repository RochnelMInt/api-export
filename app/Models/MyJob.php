<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyJob extends Model
{
    use HasFactory;

    protected $casts = [
        'expectations' => 'array',
        'benefits' => 'array',
        'qualifications' => 'array',
    ];

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function jobApplications(){

        return $this->hasMany(JobApplication::class);
    }
}
