<?php

use App\Jobs\PublishPostJob;
use App\Jobs\SyncCommentsJob;
use App\Jobs\SyncMessagesJob;
use App\Jobs\SyncMetricsJob;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\CustomNotification;
use Illuminate\Support\Facades\Schedule;

// ── PUBLISH POST TERJADWAL ──────────────────────────────────────────────────
// Cek setiap menit — cari post yang sudah waktunya dipublish
Schedule::call(function () {
    Post::scheduled()
        ->with(['socialAccount', 'media'])
        ->get()
        ->each(fn (Post $post) => PublishPostJob::dispatch($post));
})->everyMinute()->name('publish-scheduled-posts')->withoutOverlapping();

// ── SYNC PESAN ──────────────────────────────────────────────────────────────
// Ambil pesan baru dari Facebook Messenger & Instagram DM setiap 5 menit
Schedule::job(new SyncMessagesJob)
    ->everyFiveMinutes()
    ->name('sync-messages')
    ->withoutOverlapping();

// ── SYNC KOMENTAR ───────────────────────────────────────────────────────────
// Ambil komentar baru dari semua post yang sudah dipublish setiap 5 menit
Schedule::job(new SyncCommentsJob)
    ->everyFiveMinutes()
    ->name('sync-comments')
    ->withoutOverlapping();

// ── SYNC METRICS ────────────────────────────────────────────────────────────
// Update engagement metrics (likes, reach, dll) setiap jam
Schedule::job(new SyncMetricsJob)
    ->hourly()
    ->name('sync-metrics')
    ->withoutOverlapping();

// ── REMINDER POSTING ────────────────────────────────────────────────────────
// Kirim notifikasi 30 menit sebelum post dijadwalkan publish
Schedule::call(function () {
    Post::upcoming(30)
        ->with('socialAccount')
        ->get()
        ->each(function (Post $post) {
            CustomNotification::notifyUser(
                $post->created_by,
                'Reminder: Post Akan Dipublish ⏰',
                'Post "' . substr($post->caption, 0, 50) . '..." akan dipublish dalam 30 menit di ' . ucfirst($post->platform),
                'schedule',
                '/admin/posts'
            );
        });
})->everyFiveMinutes()->name('post-reminder')->withoutOverlapping();

// ── WARNING TOKEN HAMPIR EXPIRED ────────────────────────────────────────────
// Kirim notifikasi jika token akun sosial akan expired dalam 7 hari
Schedule::call(function () {
    SocialAccount::tokenExpiringSoon(7)->get()->each(function ($account) {
        CustomNotification::notifyAdmins(
            'Token Akan Expired ⚠️',
            'Token akun ' . $account->username . ' (' . ucfirst($account->platform) . ') akan expired pada ' . $account->token_expired_at->format('d M Y'),
            'warning',
            '/admin/social-accounts'
        );
    });
})->daily()->name('token-expiry-warning');