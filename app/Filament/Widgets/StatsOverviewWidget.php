<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Message;
use App\Models\Metric;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $twLikes       = Metric::where('recorded_date', '>=', now()->subDays(6)->toDateString())->sum('likes');
        $twComments    = Metric::where('recorded_date', '>=', now()->subDays(6)->toDateString())->sum('comments');
        $twReach       = Metric::where('recorded_date', '>=', now()->subDays(6)->toDateString())->sum('reach');
        $twImpressions = Metric::where('recorded_date', '>=', now()->subDays(6)->toDateString())->sum('impressions');

        $lwLikes       = Metric::whereBetween('recorded_date', [now()->subDays(13)->toDateString(), now()->subDays(7)->toDateString()])->sum('likes');
        $lwComments    = Metric::whereBetween('recorded_date', [now()->subDays(13)->toDateString(), now()->subDays(7)->toDateString()])->sum('comments');
        $lwReach       = Metric::whereBetween('recorded_date', [now()->subDays(13)->toDateString(), now()->subDays(7)->toDateString()])->sum('reach');
        $lwImpressions = Metric::whereBetween('recorded_date', [now()->subDays(13)->toDateString(), now()->subDays(7)->toDateString()])->sum('impressions');

        $unreadMessages    = Message::where('is_read', false)->count();
        $unrepliedComments = Comment::where('is_replied', false)->where('is_hidden', false)->count();
        $scheduledPosts    = Post::where('status', 'scheduled')->count();
        $publishedPosts    = Post::where('status', 'published')->count();

        return [
            Stat::make('Total Likes', $this->fmt($twLikes))
                ->description($this->trend($twLikes, $lwLikes) . ' vs minggu lalu')
                ->descriptionIcon($twLikes >= $lwLikes ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($twLikes >= $lwLikes ? 'success' : 'danger')
                ->chart($this->sparkline('likes')),

            Stat::make('Total Komentar', $this->fmt($twComments))
                ->description($unrepliedComments . ' belum dibalas')
                ->descriptionIcon('heroicon-o-chat-bubble-bottom-center-text')
                ->color('info')
                ->chart($this->sparkline('comments')),

            Stat::make('Total Reach', $this->fmt($twReach))
                ->description($this->trend($twReach, $lwReach) . ' vs minggu lalu')
                ->descriptionIcon($twReach >= $lwReach ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($twReach >= $lwReach ? 'success' : 'danger')
                ->chart($this->sparkline('reach')),

            Stat::make('Impressions', $this->fmt($twImpressions))
                ->description($this->trend($twImpressions, $lwImpressions) . ' vs minggu lalu')
                ->descriptionIcon($twImpressions >= $lwImpressions ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($twImpressions >= $lwImpressions ? 'success' : 'warning')
                ->chart($this->sparkline('impressions')),

            Stat::make('Pesan Masuk', Message::count())
                ->description($unreadMessages . ' belum dibaca')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color($unreadMessages > 0 ? 'danger' : 'success'),

            Stat::make('Post Terjadwal', $scheduledPosts)
                ->description($publishedPosts . ' published')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning'),
        ];
    }

    private function sparkline(string $col): array
    {
        return Metric::where('recorded_date', '>=', now()->subDays(6)->toDateString())
            ->selectRaw("recorded_date, SUM($col) AS total")
            ->groupBy('recorded_date')
            ->orderBy('recorded_date')
            ->pluck('total')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    private function fmt(int|float $n): string
    {
        if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
        if ($n >= 1_000)     return round($n / 1_000, 1) . 'K';
        return (string) $n;
    }

    private function trend(int|float $cur, int|float $prev): string
    {
        if ($prev == 0) return '+100%';
        $p = round(($cur - $prev) / $prev * 100, 1);
        return ($p >= 0 ? '+' : '') . $p . '%';
    }
}
