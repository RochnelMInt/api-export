<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\SocialiteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


# Socialite URLs

// La page où on présente les liens de redirection vers les providers
Route::get("login-register", "SocialiteController@loginRegister");

// La redirection vers le provider
//Route::get("/auth/redirect", "SocialiteController@redirect")->name('socialite.redirect');
Route::get("/auth/redirect", [SocialiteController::class, 'redirect'])->name('socialite.redirect');


// Le callback du provider
Route::get("/auth/google/callback", [SocialiteController::class, 'callback'])->name('socialite.callback');


Route::get('/', function () {
    return view('welcome');
});

Route::get('/email-view', function () {
    return view('mail.test');
});

Route::get('/invoice/purchase/{id}/lang/{lang}', [CartController::class, 'getInvoice'])->name('get.invoice');
Route::get('/notify-cinet-pay', [CartController::class, 'notifyCinetPay']);
Route::post('/notify-cinet-pay', [CartController::class, 'notifyCinetPay']);

Route::get('/return-cinet-pay', [CartController::class, 'returnCinetPay']);
Route::post('/return-cinet-pay', [CartController::class, 'returnCinetPay']);

//Route::get('/command', function () {
//    \Artisan::call('websocketsecure:init');
//});
