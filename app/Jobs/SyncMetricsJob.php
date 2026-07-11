<?php

namespace App\Jobs;

use App\Models\Metric;
use App\Models\SocialAccount;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function handle(FacebookService $fb, InstagramService $ig): void
    {
        $accounts = SocialAccount::all();

        foreach ($accounts as $account) {
            if ($account->isTokenExpired()) {
                Log::warning('SyncMetricsJob: Token expired untuk ' . $account->username);
                continue;
            }

            try {
                if ($account->platform === 'facebook') {
                    $this->syncFacebookMetrics($account, $fb);
                } else {
                    $this->syncInstagramMetrics($account, $ig);
                }
            } catch (\Exception $e) {
                Log::error('SyncMetricsJob error (' . $account->username . '): ' . $e->getMessage());
            }
        }
    }

    private function syncFacebookMetrics(SocialAccount $account, FacebookService $fb): void
    {
        // ── Per-post metrics ──────────────────────────────────
        // Ambil post via pivot post_social_accounts (sudah many-to-many)
        $posts = $account->posts()
            ->where('status', 'published')
            ->wherePivotNotNull('platform_post_id')
            ->get();

    foreach ($posts as $post) {

    $platformPostId = $post->pivot->platform_post_id;

        if (! $platformPostId) {
            continue;
        }

        $engagement = $fb->fetchPostEngagement(
            $account,
            $platformPostId
        );

    Metric::updateOrCreate(
        [
            'social_account_id' => $account->id,
            'post_id'           => $post->id,
            'recorded_date'     => today(),
        ],
        [
            'platform'    => 'facebook',

            // sementara
            'likes'       => $engagement['likes'] ?? 0,
            'comments'    => $engagement['comments'] ?? 0,
            'shares'      => $engagement['shares'] ?? 0,

            'reach'       => 0,
            'impressions' => 0,
        ]
    );
}
    }
    
    private function syncInstagramMetrics(SocialAccount $account, InstagramService $ig): void
    {
        // ── Per-post metrics ──────────────────────────────────
        $posts = $account->posts()
            ->where('status', 'published')
            ->wherePivotNotNull('platform_post_id')
            ->get();

        foreach ($posts as $post) {
            $platformPostId = $post->pivot->platform_post_id;
            $insights       = $ig->fetchMediaInsights($account, $platformPostId);

            if (empty($insights)) continue;

            Metric::updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'post_id'           => $post->id,
                    'recorded_date'     => today(),
                ],
                [
                    'platform'    => 'instagram',
                    'likes'       => $insights['likes']       ?? 0,
                    'comments'    => $insights['comments']    ?? 0,
                    'shares'      => $insights['shares']      ?? 0,
                    'reach'       => $insights['reach']       ?? 0,
                    'impressions' => $insights['impressions'] ?? 0,
                    'saves'       => $insights['saved']       ?? 0,
                ]
            );
        }

        // ── Account-level insights ────────────────────────────
        $accountInsights = $ig->fetchAccountInsights($account);

        // API returns [{name, period, values:[{value, end_time}]}]
        // Ambil value terbaru (index terakhir)
        $map = [];
        foreach ($accountInsights as $insight) {
            $values            = $insight['values'] ?? [];
            $map[$insight['name']] = !empty($values) ? (end($values)['value'] ?? 0) : 0;
        }

        Metric::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'post_id'           => null,
                'recorded_date'     => today(),
            ],
            [
                'platform'    => 'instagram',
                'impressions' => $map['impressions']    ?? 0,
                'reach'       => $map['reach']          ?? 0,
                'likes'       => 0,
                'comments'    => 0,
                'shares'      => 0,
            ]
        );

        Log::info('SyncMetricsJob: Instagram metrics synced untuk ' . $account->username, [
            'posts'      => $posts->count(),
            'reach'      => $map['reach'] ?? 0,
            'impressions'=> $map['impressions'] ?? 0,
        ]);
    }
}
