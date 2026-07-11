<?php

namespace App\Filament\Widgets;

use App\Models\Metric;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PostAnalyticsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                'Total Reach',
                number_format(Metric::sum('reach'))
            ),

            Stat::make(
                'Total Impressions',
                number_format(Metric::sum('impressions'))
            ),

            Stat::make(
                'Total Likes',
                number_format(Metric::sum('likes'))
            ),

            Stat::make(
                'Total Comments',
                number_format(Metric::sum('comments'))
            ),

        ];
    }
}