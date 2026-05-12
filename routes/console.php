<?php

use App\Jobs\PublishPostJob;
use App\Jobs\SyncCommentsJob;
use App\Jobs\SyncMessagesJob;
use App\Jobs\SyncMetricsJob;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\CustomNotification;
use Illuminate\Support\Facades\Schedule;

// ─────────────────────────────────────────────────────────────
Schedule::call(function () {

    \Log::info('SCHEDULER JALAN: ' . now());

    $posts = Post::where('status', 'scheduled')->get();

    \Log::info('JUMLAH POST SCHEDULED: ' . $posts->count());

    foreach ($posts as $post) {

        \Log::info('POST ID: ' . $post->id);
        \Log::info('SCHEDULED AT: ' . $post->scheduled_at);
        \Log::info('NOW: ' . now());

        if ($post->scheduled_at <= now()) {

            $post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            \Log::info('POST BERHASIL DIPUBLISH');
        }
    }

})->everyMinute()
  ->name('auto-publish-posts');


// ─────────────────────────────────────────────────────────────
// REMINDER 60 MENIT SEBELUM PUBLISH
// ─────────────────────────────────────────────────────────────
Schedule::call(function () {

    Post::where('status', 'scheduled')
        ->whereBetween('scheduled_at', [
            now()->addMinutes(59),
            now()->addMinutes(60),
        ])
        ->get()
        ->each(function (Post $post) {

            CustomNotification::notifyUser(
                $post->created_by,
                '⏰ Jadwal Posting Mendekat',
                'Posting ' . $post->platform . ' akan dipublikasikan dalam 60 menit.',
                'info',
                '/admin/posts'
            );
        });

})->everyMinute()
  ->name('post-reminder')
  ->withoutOverlapping();


// ─────────────────────────────────────────────────────────────
// SYNC PESAN
// ─────────────────────────────────────────────────────────────
Schedule::job(new SyncMessagesJob)
    ->everyFiveMinutes()
    ->name('sync-messages')
    ->withoutOverlapping();


// ─────────────────────────────────────────────────────────────
// SYNC KOMENTAR
// ─────────────────────────────────────────────────────────────
Schedule::job(new SyncCommentsJob)
    ->everyFiveMinutes()
    ->name('sync-comments')
    ->withoutOverlapping();


// ─────────────────────────────────────────────────────────────
// SYNC METRICS
// ─────────────────────────────────────────────────────────────
Schedule::job(new SyncMetricsJob)
    ->hourly()
    ->name('sync-metrics')
    ->withoutOverlapping();


// ─────────────────────────────────────────────────────────────
// TOKEN EXPIRY WARNING
// ─────────────────────────────────────────────────────────────
Schedule::call(function () {

    SocialAccount::tokenExpiringSoon(7)
        ->get()
        ->each(function ($account) {

            CustomNotification::notifyAdmins(
                'Token Akan Expired ⚠️',
                'Token akun ' . $account->username .
                ' (' . ucfirst($account->platform) . ')' .
                ' akan expired pada ' .
                $account->token_expired_at->format('d M Y'),
                'warning',
                '/admin/social-accounts'
            );
        });

})->daily()
  ->name('token-expiry-warning')
  ->withoutOverlapping();