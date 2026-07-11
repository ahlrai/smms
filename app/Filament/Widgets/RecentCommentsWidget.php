<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use Filament\Widgets\Widget;

class RecentCommentsWidget extends Widget
{
    protected string $view             = 'filament.widgets.recent-comments-widget';
    protected static ?int $sort        = 5;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasPermissionTo('comment.view') ?? false;
    }

    protected function getViewData(): array
    {
        $comments = Comment::with(['post:id,title,caption', 'socialAccount:id,platform,username'])
            ->whereNull('parent_comment_id')
            ->where('is_hidden', false)
            ->orderByDesc('commented_at')
            ->limit(5)
            ->get();

        return ['comments' => $comments];
    }
}
