<?php

namespace App\Filament\Pages;

use App\Models\CustomNotification;

use Filament\Pages\Page;

use Illuminate\Database\Eloquent\Collection;

class Notifications extends Page
{
    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-bell-alert';

    protected static ?string $navigationLabel =
        'Notifications';

    protected static string|\UnitEnum|null $navigationGroup =
        'Social Media';

    protected static ?int $navigationSort = 6;

    protected string $view =
        'filament.pages.notifications';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('notifications.view') ?? false;
    }

    public Collection $notifications;

    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    protected function loadNotifications(): void
    {
        $this->notifications = CustomNotification::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        $this->unreadCount = CustomNotification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(int $id): void
    {
        CustomNotification::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->update([
                'read_at' => now(),
            ]);

        $this->loadNotifications();
    }

    public function markAllAsRead(): void
    {
        CustomNotification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        $this->loadNotifications();
    }
}