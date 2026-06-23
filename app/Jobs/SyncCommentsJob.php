<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\CustomNotification;
use App\Models\Post;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 90;

    public function handle(FacebookService $fb, InstagramService $ig): void
    {
        Log::info('SyncCommentsJob jalan');
        // Ambil semua post yang sudah dipublish
        $posts = Post::where('status', 'published')
            ->with('socialAccounts')
            ->get();

        foreach ($posts as $post) {
            foreach ($post->socialAccounts as $account) {

        if ($account->isTokenExpired()) {
            continue;
        }

        try {

            $platformPostId =
                $account->pivot->platform_post_id;

            if (!$platformPostId) {
                continue;
            }

            if ($account->isFacebook()) {

                $this->syncFacebookComments(
                    $post,
                    $account,
                    $platformPostId,
                    $fb
                );
            }

            if ($account->isInstagram()) {

                $this->syncInstagramComments(
                    $post,
                    $account,
                    $platformPostId,
                    $ig
                );
            }

        } catch (\Exception $e) {

            Log::error(
                'SyncCommentsJob error (Post ID: '
                . $post->id .
                '): '
                . $e->getMessage()
            );
        }
    }
}
    }


    private function syncFacebookComments($post, $account, string $facebookPostID, FacebookService $fb): void
    {
        $comments = $fb->fetchComments($account, $facebookPostID);
        $newCount = 0;

        foreach ($comments as $comment) {
            $exists = Comment::where('platform_comment_id', $comment['id'])->exists();
            if ($exists) continue;

            Comment::create([
                'post_id'             => $post->id,
                'social_account_id'   => $account->id,
                'platform_comment_id' => $comment['id'],
                'commenter_id'        => $comment['from']['id'] ?? null,
                'commenter_username'  => $comment['from']['name'] ?? 'Unknown',
                'platform'            => 'facebook',
                'content'             => $comment['message'] ?? '',
                'like_count'          => $comment['like_count'] ?? 0,
                'is_replied'          => false,
                'commented_at'        => Carbon::parse($comment['created_time'] ?? now()),
            ]);

            $newCount++;
        }

        if ($newCount > 0) {
            CustomNotification::notifyUser(
                $post->created_by,
                $newCount . ' Komentar Facebook Baru 💭',
                'Post "' . substr($post->caption, 0, 40) . '..." mendapat ' . $newCount . ' komentar baru',
                'comment',
                '/admin/comments'
            );
        }
    }

    private function syncInstagramComments($post, $account, string $instagramPostId, InstagramService $ig): void
    {

        $comments = $ig->fetchComments($account, $instagramPostId);
        $newCount = 0;

        foreach ($comments as $comment) {
            $exists = Comment::where('platform_comment_id', $comment['id'])->exists();
            if ($exists) continue;

            Comment::create([
                'post_id'             => $post->id,
                'social_account_id'   => $account->id,
                'platform_comment_id' => $comment['id'],
                'commenter_id'        => $comment['from']['id'] ?? null,
                'commenter_username'  => $comment['from']['username'] ?? $comment['username'] ?? 'Unknown',
                'platform'            => 'instagram',
                'content'             => $comment['text'] ?? $comment['message']?? '',
                'like_count'          => $comment['like_count'] ?? 0,
                'is_replied'          => false,
                'commented_at'        => Carbon::parse($comment['timestamp'] ?? $comment['created_time'] ?? now()),
            ]);

            $newCount++;
        }

        if ($newCount > 0) {
            CustomNotification::notifyUser(
                $post->created_by,
                $newCount . ' Komentar Instagram Baru 💭',
                'Post "' . substr($post->caption, 0, 40) . '..." mendapat ' . $newCount . ' komentar baru',
                'comment',
                '/admin/comments'
            );
        }
    }
}