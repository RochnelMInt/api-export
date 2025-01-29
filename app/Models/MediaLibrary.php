<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaLibrary extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral',
        'type',
        'path',
    ];

    public function user(){

        return $this->belongsTo(User::class);
    }

    public function article(){

        return $this->belongsTo(Article::class);
    }

    public function job(){

        return $this->belongsTo(MyJob::class);
    }

    public function actualite(){

        return $this->belongsTo(Actualite::class);
    }

    public function category(){

        return $this->belongsTo(Category::class);
    }

    public function jobApplication(){

        return $this->belongsTo(JobApplication::class);
    }
}
