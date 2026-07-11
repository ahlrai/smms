<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EngagementChartWidget;
use App\Filament\Widgets\PlatformInsightsWidget;
use App\Filament\Widgets\PlatformSplitWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\PlatformPerformanceWidget;
use Filament\Pages\Page;

class AnalyticsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';
    protected static ?string $navigationLabel                = 'Analytics Overall';
    protected static ?string $title                          = 'Analytics Overall';
    protected static ?int    $navigationSort                 = 2;
    protected string $view = 'filament.pages.analytics-page';


    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            PlatformInsightsWidget::class,
            PlatformPerformanceWidget::class,
            EngagementChartWidget::class,
            PlatformSplitWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}
