<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function __construct(
        private FacebookService  $fb,
        private InstagramService $ig
    ) {}

    // ── FACEBOOK ────────────────────────────────────────────────

    /**
     * Redirect ke halaman OAuth Facebook
     */
    public function redirectToFacebook(): RedirectResponse
    {
        return redirect($this->fb->getOAuthUrl());
    }

    /**
     * Handle callback setelah user authorize di Facebook
     */
    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        // Cek apakah user cancel
        if ($request->has('error')) {
            return redirect('/admin/social-accounts')
                ->with('error', 'Koneksi Facebook dibatalkan.');
        }

        // Validasi state (CSRF protection)
        if ($request->state !== session()->token()) {
            return redirect('/admin/social-accounts')
                ->with('error', 'Invalid state. Silakan coba lagi.');
        }

        try {
            // Step 1: Tukar code dengan short-lived token
            $shortToken = $this->fb->getShortLivedToken($request->code);

            if (!isset($shortToken['access_token'])) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Gagal mendapatkan token Facebook.');
            }

            // Step 2: Tukar dengan long-lived token (~60 hari)
            $longToken = $this->fb->getLongLivedToken($shortToken['access_token']);

            if (!isset($longToken['access_token'])) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Gagal mendapatkan long-lived token.');
            }

            // Step 3: Ambil semua Facebook Pages yang dikelola
            $pages = $this->fb->getPages($longToken['access_token']);

            if (empty($pages)) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Tidak ada Facebook Page yang ditemukan. Pastikan akun memiliki Page.');
            }

            // Step 4: Simpan setiap Page ke database
            foreach ($pages as $page) {
                SocialAccount::updateOrCreate(
                    [
                        'platform'   => 'facebook',
                        'account_id' => $page['id'],
                    ],
                    [
                        'username'         => $page['name'],
                        'access_token'     => $page['access_token'], // Token per Page
                        'token_expired_at' => now()->addDays(60),
                        'created_by'       => auth()->id(),
                    ]
                );
            }

            return redirect('/admin/social-accounts')
                ->with('success', count($pages) . ' Facebook Page berhasil terhubung!');

        } catch (\Exception $e) {
            Log::error('Facebook OAuth error: ' . $e->getMessage());
            return redirect('/admin/social-accounts')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ── INSTAGRAM ───────────────────────────────────────────────

    /**
     * Redirect ke halaman OAuth Instagram
     */
    public function redirectToInstagram(): RedirectResponse
    {
        return redirect($this->ig->getOAuthUrl());
    }

    /**
     * Handle callback setelah user authorize di Instagram
     */
    public function handleInstagramCallback(Request $request): RedirectResponse
    {
        // Cek apakah user cancel
        if ($request->has('error')) {
            return redirect('/admin/social-accounts')
                ->with('error', 'Koneksi Instagram dibatalkan.');
        }

        try {
            // Step 1: Tukar code dengan token (pakai Facebook OAuth)
            $shortToken = $this->fb->getShortLivedToken($request->code);

            if (!isset($shortToken['access_token'])) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Gagal mendapatkan token.');
            }

            // Step 2: Long-lived token
            $longToken = $this->fb->getLongLivedToken($shortToken['access_token']);

            if (!isset($longToken['access_token'])) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Gagal mendapatkan long-lived token.');
            }

            // Step 3: Ambil Facebook Pages untuk cari Instagram Business Account
            $pages = $this->fb->getPages($longToken['access_token']);

            if (empty($pages)) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Tidak ada Facebook Page ditemukan. Instagram Business Account harus terhubung ke Facebook Page.');
            }

            $connected = 0;

            // Step 4: Untuk setiap Page, cari Instagram Business Account-nya
            foreach ($pages as $page) {
                $igAccountId = $this->ig->getInstagramAccountId($page['id'], $page['access_token']);

                if (!$igAccountId) continue;

                // Ambil profil Instagram
                $tempAccount        = new SocialAccount();
                $tempAccount->account_id    = $igAccountId;
                // Set token langsung ke attribute agar bisa dipakai sementara
                $tempAccount->setRawAttributes(['access_token' => encrypt($page['access_token'])]);

                // Simpan ke database
                SocialAccount::updateOrCreate(
                    [
                        'platform'   => 'instagram',
                        'account_id' => $igAccountId,
                    ],
                    [
                        'username'         => '@' . ($page['name'] ?? 'instagram'),
                        'access_token'     => $page['access_token'],
                        'token_expired_at' => now()->addDays(60),
                        'created_by'       => auth()->id(),
                    ]
                );

                $connected++;
            }

            if ($connected === 0) {
                return redirect('/admin/social-accounts')
                    ->with('error', 'Tidak ada Instagram Business Account ditemukan. Pastikan akun Instagram sudah terhubung ke Facebook Page.');
            }

            return redirect('/admin/social-accounts')
                ->with('success', $connected . ' Instagram Business Account berhasil terhubung!');

        } catch (\Exception $e) {
            Log::error('Instagram OAuth error: ' . $e->getMessage());
            return redirect('/admin/social-accounts')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ── DISCONNECT ──────────────────────────────────────────────

    /**
     * Putuskan koneksi akun sosial
     */
    public function disconnect(int $id): RedirectResponse
    {
        $account = SocialAccount::findOrFail($id);

        // Pastikan hanya admin atau pemilik yang bisa disconnect
        if (!auth()->user()->hasRole('admin') && $account->created_by !== auth()->id()) {
            return redirect('/admin/social-accounts')
                ->with('error', 'Tidak memiliki akses.');
        }

        $account->delete();

        return redirect('/admin/social-accounts')
            ->with('success', 'Akun berhasil diputus.');
    }
}