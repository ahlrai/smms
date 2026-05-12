<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class Notifications extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notifications';

    protected static string | \UnitEnum | null $navigationGroup = 'Social Media';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.notifications';

    public Collection $notifications;

    public function mount(): void
    {
        $this->notifications = auth()
            ->user()
            ->notifications()
            ->latest()
            ->get();
    }
}