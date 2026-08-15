<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\InquiryAttachmentController;
use App\Http\Controllers\PortalRequestAttachmentController;
use App\Http\Controllers\SourcingRequestAttachmentController;
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

// Serve sourcing-request attachments from the private disk; auth-gated.
Route::get('/sourcing-request-attachments/{attachment}/download', SourcingRequestAttachmentController::class)
    ->middleware('auth')
    ->name('sourcing-request-attachment.download');

// Serve portal-request attachments from the private disk; auth-gated, plus an
// ownership check inside the controller for portal (customer) accounts.
Route::get('/portal-request-attachments/{attachment}/download', PortalRequestAttachmentController::class)
    ->middleware('auth')
    ->name('portal-request-attachment.download');

