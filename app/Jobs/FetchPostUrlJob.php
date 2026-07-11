<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPostUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $postId
    ) {}

    public function handle(): void
    {
        Log::info('=== FETCH POST URL JOB JALAN ===');

        Log::info([
            'post_id' => $this->postId,
        ]);

        $post = Post::find($this->postId);

        if (!$post) {

            Log::warning('Post tidak ditemukan');

            return;
        }

        Log::info($post->toArray());

        foreach ($post->socialAccounts as $account) {

            if (strtolower($account->platform) !== 'facebook') {
                continue;
            }

            $platformPostId = $account->pivot->platform_post_id;

            if (!$platformPostId) {

                Log::warning('Platform Post ID kosong', [
                    'account_id' => $account->id,
                ]);

                continue;
            }

            try {

                $response = Http::get(

                    "https://graph.facebook.com/v22.0/{$platformPostId}",

                    [
                        'fields' => 'permalink_url',

                        'access_token' => $account->access_token,
                    ]

                )->json();

                Log::info('FACEBOOK PERMALINK RESPONSE', $response);

                $postUrl = $response['permalink_url'] ?? null;

                if (!$postUrl) {

                    Log::warning('Permalink Facebook belum tersedia');

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE POSTS
                |--------------------------------------------------------------------------
                */

                $post->update([
                    'post_url' => $postUrl,
                ]);

                /*
                |--------------------------------------------------------------------------
                | UPDATE PIVOT
                |--------------------------------------------------------------------------
                */

                $post->socialAccounts()->updateExistingPivot(
                    $account->id,
                    [
                        'post_url' => $postUrl,
                    ]
                );

                Log::info('Facebook URL berhasil disimpan', [
                    'post_id' => $post->id,
                    'url' => $postUrl,
                ]);

            } catch (\Exception $e) {

                Log::error(
                    'Fetch Facebook URL gagal: ' .
                    $e->getMessage()
                );
            }
        }
    }
}