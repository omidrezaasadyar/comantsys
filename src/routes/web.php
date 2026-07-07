<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\InquiryAttachmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Serve user avatars from the private disk; auth-gated (session cookie on <img>).
Route::get('/user/{user}/avatar', AvatarController::class)
    ->middleware('auth')
    ->name('user.avatar');

// Serve inquiry attachments from the private disk; auth-gated.
Route::get('/inquiry-attachments/{attachment}/download', InquiryAttachmentController::class)
    ->middleware('auth')
    ->name('inquiry-attachment.download');

