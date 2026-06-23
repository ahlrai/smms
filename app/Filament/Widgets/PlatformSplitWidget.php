<?php

namespace App\Filament\Widgets;

use App\Models\Metric;
use App\Models\Post;
use Filament\Widgets\ChartWidget;

class PlatformSplitWidget extends ChartWidget
{
    protected ?string $heading                 = 'Platform Split';
    protected static ?int $sort                = 3;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $fb = Metric::where('platform', 'facebook')
            ->where('recorded_date', '>=', now()->subDays(6)->toDateString())
            ->sum('reach');

        $ig = Metric::where('platform', 'instagram')
            ->where('recorded_date', '>=', now()->subDays(6)->toDateString())
            ->sum('reach');

        // Fallback to published post counts when no metric reach data exists
        if (($fb + $ig) == 0) {
            $fb = Post::where('status', 'published')
                ->whereHas('socialAccounts', fn ($q) => $q->where('platform', 'facebook'))
                ->count();
            $ig = Post::where('status', 'published')
                ->whereHas('socialAccounts', fn ($q) => $q->where('platform', 'instagram'))
                ->count();
        }

        $total = $fb + $ig;

        return [
            'datasets' => [
                [
                    'data'            => [$fb, $ig],
                    'backgroundColor' => ['#1877F2', '#E1306C'],
                    'borderWidth'     => 0,
                    'hoverOffset'     => 6,
                ],
            ],
            'labels' => [
                'Facebook (' . ($total > 0 ? round($fb / $total * 100) : 0) . '%)',
                'Instagram (' . ($total > 0 ? round($ig / $total * 100) : 0) . '%)',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'bottom',
                    'labels'   => ['padding' => 16, 'usePointStyle' => true],
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
