<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class NotificationWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-widget';

    public static function canView(): bool
    {
        return auth()->user()?->hasPermissionTo('notifications.view') ?? false;
    }
}
