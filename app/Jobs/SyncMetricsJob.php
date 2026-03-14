<?php

namespace App\Jobs;

use App\Models\Metric;
use App\Models\Post;
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
    public int $timeout = 120;

    public function handle(FacebookService $fb, InstagramService $ig): void
    {
        $accounts = SocialAccount::all();

        foreach ($accounts as $account) {
            if ($account->isTokenExpired()) continue;

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
        // Sync metrics per post
        $posts = Post::where('social_account_id', $account->id)
            ->where('status', 'published')
            ->whereNotNull('platform_post_id')
            ->get();

        foreach ($posts as $post) {
            $engagement = $fb->fetchPostEngagement($account, $post->platform_post_id);

            Metric::updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'post_id'           => $post->id,
                    'recorded_date'     => today(),
                ],
                [
                    'platform'    => 'facebook',
                    'likes'       => $engagement['likes'] ?? 0,
                    'comments'    => $engagement['comments'] ?? 0,
                    'shares'      => $engagement['shares'] ?? 0,
                    'reach'       => 0,
                    'impressions' => 0,
                ]
            );
        }

        // Sync metrics level akun
        $insights = $fb->fetchPageInsights($account);

        $metricMap = [];
        foreach ($insights as $insight) {
            $metricMap[$insight['name']] = $insight['values'][0]['value'] ?? 0;
        }

        Metric::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'post_id'           => null,
                'recorded_date'     => today(),
            ],
            [
                'platform'    => 'facebook',
                'impressions' => $metricMap['page_impressions'] ?? 0,
                'reach'       => $metricMap['page_reach'] ?? 0,
                'likes'       => $metricMap['page_post_engagements'] ?? 0,
            ]
        );

        Log::info('SyncMetricsJob: Facebook metrics synced untuk ' . $account->username);
    }

    private function syncInstagramMetrics(SocialAccount $account, InstagramService $ig): void
    {
        // Sync metrics per post
        $posts = Post::where('social_account_id', $account->id)
            ->where('status', 'published')
            ->whereNotNull('platform_post_id')
            ->get();

        foreach ($posts as $post) {
            $insights = $ig->fetchMediaInsights($account, $post->platform_post_id);

            Metric::updateOrCreate(
                [
                    'social_account_id' => $account->id,
                    'post_id'           => $post->id,
                    'recorded_date'     => today(),
                ],
                [
                    'platform'    => 'instagram',
                    'likes'       => $insights['likes'] ?? 0,
                    'comments'    => $insights['comments'] ?? 0,
                    'shares'      => $insights['shares'] ?? 0,
                    'reach'       => $insights['reach'] ?? 0,
                    'impressions' => $insights['impressions'] ?? 0,
                    'saves'       => $insights['saved'] ?? 0,
                ]
            );
        }

        // Sync metrics level akun
        $accountInsights = $ig->fetchAccountInsights($account);

        $metricMap = [];
        foreach ($accountInsights as $insight) {
            $metricMap[$insight['name']] = $insight['values'][0]['value'] ?? 0;
        }

        Metric::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'post_id'           => null,
                'recorded_date'     => today(),
            ],
            [
                'platform'    => 'instagram',
                'impressions' => $metricMap['impressions'] ?? 0,
                'reach'       => $metricMap['reach'] ?? 0,
            ]
        );

        Log::info('SyncMetricsJob: Instagram metrics synced untuk ' . $account->username);
    }
}