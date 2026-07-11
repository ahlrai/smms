<?php

namespace App\Filament\Resources\PostAnalytics\Pages;

use App\Filament\Resources\PostAnalytics\PostAnalyticsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPostAnalytics extends EditRecord
{
    protected static string $resource = PostAnalyticsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
