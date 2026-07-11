<?php

namespace App\Filament\Resources\PostAnalytics\Pages;

use App\Filament\Resources\PostAnalytics\PostAnalyticsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPostAnalytics extends ViewRecord
{
    protected static string $resource = PostAnalyticsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
