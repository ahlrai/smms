<?php

use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

// Landing Page — public homepage, redirect ke dashboard jika sudah login
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/admin');
    }
    return view('landing');
})->name('home');

// ── SOCIAL MEDIA OAUTH ROUTES ───────────────────────────────────────────────
// Semua route OAuth harus dalam middleware auth agar hanya user login yang bisa akses

Route::middleware(['auth'])->prefix('auth')->group(function () {

    // Facebook
    Route::get('/facebook/redirect', [SocialAuthController::class, 'redirectToFacebook'])
        ->name('auth.facebook.redirect');

    Route::get('/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback'])
        ->name('auth.facebook.callback');

    // Instagram
    Route::get('/instagram/redirect', [SocialAuthController::class, 'redirectToInstagram'])
        ->name('auth.instagram.redirect');

    Route::get('/instagram/callback', [SocialAuthController::class, 'handleInstagramCallback'])
        ->name('auth.instagram.callback');

    // Disconnect akun
    Route::delete('/disconnect/{id}', [SocialAuthController::class, 'disconnect'])
        ->name('auth.disconnect');
});