<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\Widget;

class RecentPostsWidget extends Widget
{
    protected string $view                     = 'filament.widgets.recent-posts-widget';
    protected static ?int $sort                = 5;
    protected int | string | array $columnSpan = 1;

    public function getPosts(): \Illuminate\Database\Eloquent\Collection
    {
        return Post::with(['socialAccount', 'metrics'])
            ->latest()
            ->limit(5)
            ->get();
    }
}
