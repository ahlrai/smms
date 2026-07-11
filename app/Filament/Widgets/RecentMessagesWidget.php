<?php

namespace App\Filament\Widgets;

use App\Models\Message;
use Filament\Widgets\Widget;

class RecentMessagesWidget extends Widget
{
    protected string $view                     = 'filament.widgets.recent-messages-widget';
    protected static ?int $sort                = 4;
    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasPermissionTo('message.view') ?? false;
    }

    public function getMessages(): \Illuminate\Database\Eloquent\Collection
    {
        return Message::with('socialAccount')
            ->latest('sent_at')
            ->limit(5)
            ->get();
    }
}
