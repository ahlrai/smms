<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Message;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyPermission(['post.create', 'post.edit', 'message.view', 'comment.view']) ?? false;
    }

    protected function getStats(): array
    {
        $publishedPosts    = Post::where('status', 'published')->count();
        $scheduledPosts    = Post::where('status', 'scheduled')->count();
        $unrepliedComments = Comment::where('is_replied', false)->where('is_hidden', false)->count();
        $unreadMessages    = Message::where('is_read', false)->count();

        return [
            Stat::make('Total Post Terbit', $publishedPosts)
                ->description($scheduledPosts . ' post terjadwal')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Komentar Belum Dibalas', $unrepliedComments)
                ->description($unrepliedComments > 0 ? 'Perlu diperhatikan' : 'Semua sudah dibalas')
                ->descriptionIcon('heroicon-o-chat-bubble-bottom-center-text')
                ->color($unrepliedComments > 0 ? 'warning' : 'success'),

            Stat::make('Pesan Belum Dibaca', $unreadMessages)
                ->description($unreadMessages > 0 ? 'Ada pesan baru masuk' : 'Semua sudah dibaca')
                ->descriptionIcon('heroicon-o-envelope')
                ->color($unreadMessages > 0 ? 'danger' : 'success'),
        ];
    }
}
