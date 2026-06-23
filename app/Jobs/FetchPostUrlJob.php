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

    public int $tries   = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(public int $postId) {}

    public function handle(): void
    {
        $post = Post::with('socialAccounts')->find($this->postId);

        if (!$post || $post->status !== 'published') {
            return;
        }

        $firstUrl = null;

        foreach ($post->socialAccounts as $account) {
            $pid = $account->pivot->platform_post_id;
            if (!$pid) {
                continue;
            }

            // Skip if this account's URL is already saved
            if ($account->pivot->post_url) {
                if (!$firstUrl) $firstUrl = $account->pivot->post_url;
                continue;
            }

            $url = null;

            try {
                if ($account->isInstagram()) {
                    $res = Http::timeout(15)->get(
                        "https://graph.facebook.com/v22.0/{$pid}",
                        ['fields' => 'permalink', 'access_token' => $account->access_token]
                    )->json();
                    $url = $res['permalink'] ?? null;
                    if (isset($res['error'])) {
                        Log::warning('FetchPostUrlJob IG: ' . ($res['error']['message'] ?? 'unknown'));
                    }
                } elseif ($account->isFacebook()) {
                    $res = Http::timeout(15)->get(
                        "https://graph.facebook.com/v22.0/{$pid}",
                        ['fields' => 'permalink_url', 'access_token' => $account->access_token]
                    )->json();
                    $url = $res['permalink_url'] ?? null;
                    if (isset($res['error'])) {
                        Log::warning('FetchPostUrlJob FB: ' . ($res['error']['message'] ?? 'unknown'));
                    }
                }
            } catch (\Exception $e) {
                Log::error("FetchPostUrlJob [{$account->platform}] post={$this->postId}: " . $e->getMessage());
                continue;
            }

            if ($url) {
                $post->socialAccounts()->updateExistingPivot($account->id, ['post_url' => $url]);
                Log::info("FetchPostUrlJob: {$account->platform} post={$this->postId} → {$url}");
                if (!$firstUrl) $firstUrl = $url;
            }
        }

        // Mirror the first URL to posts.post_url for easy display in the table
        if ($firstUrl && $post->post_url !== $firstUrl) {
            $post->update(['post_url' => $firstUrl]);
        }
    }
}
