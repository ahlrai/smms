<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\SocialAccount;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlatformInsightsWidget extends Widget
{
    protected string $view                  = 'filament.widgets.platform-insights';
    protected static ?int $sort             = 4;
    protected int|string|array $columnSpan  = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasPermissionTo('analytics.view') ?? false;
    }

    protected function getViewData(): array
    {
        $data = Cache::remember('platform_insights_widget', now()->addMinutes(30), function () {
            return $this->fetchInsights();
        });

        return ['platforms' => $data];
    }

    private function fetchInsights(): array
    {
        $result = [];

        foreach (['facebook', 'instagram'] as $platform) {
            $accounts = SocialAccount::where('platform', $platform)->get();

            $postCount = Post::where('status', 'published')
                ->whereHas('socialAccounts', fn ($q) => $q->where('platform', $platform))
                ->count();

            $accountData   = [];
            $totalAudience = 0;

            foreach ($accounts as $account) {
                $audience = 0;

                try {
                    if ($platform === 'facebook') {
                        $res = Http::timeout(10)->get(
                            'https://graph.facebook.com/v22.0/' . $account->account_id,
                            ['fields' => 'fan_count', 'access_token' => $account->access_token]
                        )->json();
                        $audience = $res['fan_count'] ?? 0;

                        if (isset($res['error'])) {
                            Log::warning('PlatformInsights FB [' . $account->username . ']: ' . ($res['error']['message'] ?? 'unknown'));
                        }
                    } else {
                        $res = Http::timeout(10)->get(
                            'https://graph.facebook.com/v22.0/' . $account->account_id,
                            ['fields' => 'followers_count', 'access_token' => $account->access_token]
                        )->json();
                        $audience = $res['followers_count'] ?? 0;

                        if (isset($res['error'])) {
                            Log::warning('PlatformInsights IG [' . $account->username . ']: ' . ($res['error']['message'] ?? 'unknown'));
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("PlatformInsights [{$platform}/{$account->username}]: " . $e->getMessage());
                }

                $accountData[]  = ['username' => $account->username, 'audience' => $audience];
                $totalAudience += $audience;
            }

            $result[$platform] = [
                'accounts'       => $accountData,
                'total_posts'    => $postCount,
                'total_audience' => $totalAudience,
            ];
        }

        return $result;
    }
}
