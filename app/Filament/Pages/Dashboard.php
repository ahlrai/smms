<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ConnectedAccountsWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\RecentMessagesWidget;
use App\Filament\Widgets\RecentPostsWidget;
use App\Filament\Widgets\RecentCommentsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function canAccess(): bool
    {
        try {
            return auth()->user()?->hasPermissionTo('panel.access') ?? false;
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return auth()->user()?->roles()->exists() ?? false;
        }
    }
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title           = 'Dashboard';
    protected static ?int    $navigationSort  = 1;

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
            ConnectedAccountsWidget::class,
            RecentMessagesWidget::class,
            RecentPostsWidget::class,
            RecentCommentsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $name = auth()->user()?->name ?? 'Admin';
        $date = now()->locale('id')->isoFormat('dddd, D MMMM Y');

        return "Selamat datang, {$name}! — {$date}";
    }
}
