<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    // use HasFactory, Notifiable;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'avatar',
        'phone',
        'question',
        'answer',
        'address',
        'password',
        'comment_privacy',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function mediaLibraries(){

        return $this->hasMany(MediaLibrary::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function jobApplications(){

        return $this->hasMany(JobApplication::class);
    }

    public function userMessages()
    {
        return $this->hasMany(UserMessage::class);
    }

//    public function comments(){
//
//        return $this->hasMany(Comment::class);
//    }
//
//    public function likes(){
//
//        return $this->hasMany(Comment::class);
//    }
//
//    public function chats(){
//
//        return $this->hasMany(Chat::class, 'from_user_id');
//    }
//
//    public function postStatus(){
//
//        return $this->hasMany(PostUserStatus::class, 'user_id');
//    }
}
