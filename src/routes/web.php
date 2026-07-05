<?php

use App\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Serve user avatars from the private disk; auth-gated (session cookie on <img>).
Route::get('/user/{user}/avatar', AvatarController::class)
    ->middleware('auth')
    ->name('user.avatar');

