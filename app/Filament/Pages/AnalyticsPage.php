<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EngagementChartWidget;
use App\Filament\Widgets\PlatformInsightsWidget;
use App\Filament\Widgets\PlatformSplitWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Page;

class AnalyticsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel                = 'Analytics';
    protected static ?string $title                          = 'Analytics';
    protected static ?int    $navigationSort                 = 2;
    protected string $view = 'filament.pages.analytics-page';


    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            EngagementChartWidget::class,
            PlatformSplitWidget::class,
            PlatformInsightsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}
