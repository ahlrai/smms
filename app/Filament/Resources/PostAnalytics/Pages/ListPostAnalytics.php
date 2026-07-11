<?php

namespace App\Filament\Resources\PostAnalytics\Pages;

use App\Filament\Resources\PostAnalytics\PostAnalyticsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\PostAnalyticsStats;

class ListPostAnalytics extends ListRecords
{
    protected static string $resource = PostAnalyticsResource::class;

}
