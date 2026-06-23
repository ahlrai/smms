```php
<?php

use App\Jobs\PublishPostJob;
use App\Jobs\SyncCommentsJob;
use App\Jobs\SyncMessagesJob;
use App\Jobs\SyncMetricsJob;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\CustomNotification;

use Illuminate\Support\Facades\Schedule;


/*
|--------------------------------------------------------------------------
| AUTO PUBLISH POST
|--------------------------------------------------------------------------
*/

Schedule::call(function () {

    \Log::info('SCHEDULER JALAN: ' . now());

    $posts = Post::where('status', 'scheduled')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->get();

    \Log::info('JUMLAH POST SCHEDULED: ' . $posts->count());

    foreach ($posts as $post) {

        \Log::info('DISPATCH POST ID: ' . $post->id);

        /*
        |--------------------------------------------------------------------------
        | DISPATCH JOB
        |--------------------------------------------------------------------------
        */

        PublishPostJob::dispatch($post->id);

    }

})->everyMinute()
  ->name('auto-publish-posts')
  ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| REMINDER 60 MENIT SEBELUM PUBLISH
|--------------------------------------------------------------------------
*/

Schedule::call(function () {

    Post::where('status', 'scheduled')
        ->whereBetween('scheduled_at', [

            now()->addMinutes(59),

            now()->addMinutes(60),

        ])
        ->get()

        ->each(function (Post $post) {

            CustomNotification::notifyUser(
    userId: $post->created_by,

    title: '⏰ Jadwal Posting Mendekat',

    message:
        'Konten "' .
        $post->title .
        '" akan dipublikasikan dalam 60 menit.',

    type: 'warning',

    platform: $post->platform,

    postTitle: $post->title,

    status: 'scheduled',

    postUrl: null,

    actionUrl: '/admin/posts'
);

        });

})->everyMinute()
  ->name('post-reminder')
  ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| SYNC PESAN
|--------------------------------------------------------------------------
*/

Schedule::job(new SyncMessagesJob)

    ->everyMinute()

    ->name('sync-messages')

    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| SYNC KOMENTAR
|--------------------------------------------------------------------------
*/

Schedule::job(new SyncCommentsJob)

    ->everyFiveMinutes()

    ->name('sync-comments')

    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| SYNC METRICS
|--------------------------------------------------------------------------
*/

Schedule::job(new SyncMetricsJob)

    ->hourly()

    ->name('sync-metrics')

    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| TOKEN EXPIRY WARNING
|--------------------------------------------------------------------------
*/

Schedule::call(function () {

    SocialAccount::tokenExpiringSoon(7)

        ->get()

        ->each(function ($account) {

            CustomNotification::notifyAdmins(

                'Token Akan Expired ⚠️',

                'Token akun '
                . $account->username
                . ' ('
                . ucfirst($account->platform)
                . ') akan expired pada '
                . $account->token_expired_at->format('d M Y'),

                'warning',

                '/admin/social-accounts'

            );

        });

})->daily() 
->name('token-expiry-warning') 
->withoutOverlapping();