<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\CustomNotification;

use App\Services\FacebookService;
use App\Services\InstagramService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    /*
    |--------------------------------------------------------------------------
    | KIRIM ID POST
    |--------------------------------------------------------------------------
    */

    public function __construct(
        public int $postId
    ) {}

    /*
    |--------------------------------------------------------------------------
    | HANDLE
    |--------------------------------------------------------------------------
    */

    public function handle(

        FacebookService $fb,

        InstagramService $ig

    ): void {

        /*
        |--------------------------------------------------------------------------
        | AMBIL POST
        |--------------------------------------------------------------------------
        */

        $post = Post::find($this->postId);

        if (!$post) {

            Log::error(
                'Post tidak ditemukan ID: '
                    . $this->postId
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CEK SUDAH PUBLISHED
        |--------------------------------------------------------------------------
        */

        if ($post->status === 'published') {

            return;
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | AMBIL AKUN
            |--------------------------------------------------------------------------
            */

            $accounts = $post->socialAccounts;

            if ($accounts->isEmpty()) {

                throw new \Exception(
                    'Tidak ada akun sosial.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | LOOP AKUN
            |--------------------------------------------------------------------------
            */

            foreach ($accounts as $account) {

                /*
                |--------------------------------------------------------------------------
                | INSTAGRAM
                |--------------------------------------------------------------------------
                */

                if (
                    strtolower($account->platform)
                    ===
                    'instagram'
                ) {

                    $result =
                        \App\Filament\Resources\Posts\PostResource
                        ::publishInstagram($post);

                    if (!$result['success']) {

                        throw new \Exception(
                            $result['message']
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | FACEBOOK
                |--------------------------------------------------------------------------
                */

                else {

                    Log::info(
                        'Facebook publish belum diimplementasi.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE SUCCESS
            |--------------------------------------------------------------------------
            */

            $post->update([

                'status' => 'published',

                'published_at' => now(),

                'error_message' => null,

            ]);

            /*
            |--------------------------------------------------------------------------
            | NOTIF SUCCESS
            |--------------------------------------------------------------------------
            */

            CustomNotification::notifyUser(
    userId: $post->created_by,

    title: 'Post Berhasil Dipublish ✅',

    message: 'Konten berhasil dipublikasikan sesuai jadwal.',

    type: 'success',

    platform: $post->platform,

    postTitle: $post->title,

    status: 'published',

    postUrl: $post->post_url,

    actionUrl: '/admin/posts'
);

            Log::info(
                'POST BERHASIL DIPUBLISH ID: '
                    . $post->id
            );
        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | UPDATE FAILED
            |--------------------------------------------------------------------------
            */

            $post->update([

                'status' => 'failed',

                'error_message' =>
                $e->getMessage(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | NOTIF FAILED
            |--------------------------------------------------------------------------
            */

            CustomNotification::notifyUser(
    userId: $post->created_by,

    title: 'Post Gagal Dipublish ❌',

    message: $e->getMessage(),

    type: 'danger',

    platform: $post->platform,

    postTitle: $post->title,

    status: 'failed',

    postUrl: null,

    actionUrl: '/admin/posts'
);

            Log::error(
                'PUBLISH GAGAL: '
                    . $e->getMessage()
            );

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FAILED
    |--------------------------------------------------------------------------
    */

    public function failed(
        \Throwable $exception
    ): void {

        Log::error(

            'PublishPostJob FINAL FAILED: '

                . $exception->getMessage()

        );
    }
}