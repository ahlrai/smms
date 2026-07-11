<?php

namespace App\Filament\Widgets;

use App\Models\SocialAccount;
use Filament\Widgets\Widget;

class ConnectedAccountsWidget extends Widget
{
    protected string $view                  = 'filament.widgets.connected-accounts';
    protected static ?int $sort            = 3;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasPermissionTo('social.manage') ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'accounts' => SocialAccount::orderBy('platform')->get(),
        ];
    }
}
