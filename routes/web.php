<?php

use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\WebhookController;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing page
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return auth()->check()
        ? redirect('/admin')
        : redirect('/admin/login');

})->name('home');


/*
|--------------------------------------------------------------------------
| SOCIAL AUTH
|--------------------------------------------------------------------------
| JANGAN pakai middleware auth di callback
| supaya Meta bisa redirect balik tanpa login ulang
*/

// ================= FACEBOOK =================

Route::get(
    '/auth/facebook/redirect',
    [SocialAuthController::class, 'redirectToFacebook']
)->name('auth.facebook.redirect');


Route::get(
    '/auth/facebook/callback',
    [SocialAuthController::class, 'handleFacebookCallback']
)->name('auth.facebook.callback');


// ================= INSTAGRAM =================

Route::get(
    '/auth/instagram/redirect',
    [SocialAuthController::class, 'redirectToInstagram']
)->name('auth.instagram.redirect');


Route::get(
    '/auth/instagram/callback',
    [SocialAuthController::class, 'handleInstagramCallback']
)->name('auth.instagram.callback');


/*
|--------------------------------------------------------------------------
| 🔥 WEBHOOK INSTAGRAM MESSAGING (FR-02 CORE)
|--------------------------------------------------------------------------
| WAJIB TANPA AUTH MIDDLEWARE
| HARUS PUBLIC ACCESS
*/

// VERIFIKASI WEBHOOK (Meta GET)
Route::get(
    '/webhook',
    [WebhookController::class, 'verify']
);

// TERIMA EVENT DM INSTAGRAM (Meta POST)
Route::post(
    '/webhook',
    [WebhookController::class, 'handle']
);


/*
|--------------------------------------------------------------------------
| TEST FACEBOOK PAGE
|--------------------------------------------------------------------------
| Untuk cek apakah Meta mengembalikan page
*/

Route::get('/test-pages', function () {

    $account = SocialAccount::latest()->first();

    if (!$account) {
        return response()->json([
            'error' => 'Tidak ada token tersimpan'
        ]);
    }

    $response = Http::get(
        'https://graph.facebook.com/v22.0/me/accounts',
        [
            'fields' => 'id,name,access_token,instagram_business_account',
            'access_token' => $account->access_token
        ]
    );

    return response()->json([
        'token' => substr($account->access_token, 0, 30) . '...',
        'response' => $response->json()
    ]);
});


/*
|--------------------------------------------------------------------------
| DISCONNECT
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::delete(
        '/auth/disconnect/{id}',
        [SocialAuthController::class, 'disconnect']
    )->name('auth.disconnect');
});


/*
|--------------------------------------------------------------------------
| LOGIN REDIRECT
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');