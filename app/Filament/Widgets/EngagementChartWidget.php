<?php

namespace App\Filament\Widgets;

use App\Models\Metric;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class EngagementChartWidget extends ChartWidget
{
    protected ?string $heading                 = 'Engagement Trend';
    protected static ?int $sort                = 2;
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'weekly';

    protected function getFilters(): ?array
    {
        return [
            'weekly'  => 'Mingguan (7 hari)',
            'monthly' => 'Bulanan (30 hari)',
        ];
    }

    protected function getData(): array
    {
        $days  = ($this->filter ?? 'weekly') === 'monthly' ? 30 : 7;
        $start = now()->subDays($days - 1)->startOfDay();
        $end   = now()->endOfDay();

        $period   = CarbonPeriod::create($start->toDateString(), $end->toDateString());
        $labels   = [];
        $dateKeys = [];

        foreach ($period as $date) {
            $labels[]   = $date->format($days > 7 ? 'd M' : 'D');
            $dateKeys[] = $date->toDateString(); // "2026-03-09"
        }

        // keyBy pakai toDateString() agar cocok dengan $dateKeys (bukan Carbon __toString)
        $metrics = Metric::whereBetween('recorded_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('recorded_date,
                SUM(likes)    AS total_likes,
                SUM(comments) AS total_comments,
                SUM(reach)    AS total_reach')
            ->groupBy('recorded_date')
            ->get()
            ->keyBy(fn ($m) => Carbon::parse($m->recorded_date)->toDateString());

        $likes = $comments = $reach = [];

        foreach ($dateKeys as $key) {
            $row        = $metrics[$key] ?? null;
            $likes[]    = (int) ($row?->total_likes    ?? 0);
            $comments[] = (int) ($row?->total_comments ?? 0);
            $reach[]    = (int) ($row?->total_reach    ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Likes',
                    'data'            => $likes,
                    'borderColor'     => '#f43f5e',
                    'backgroundColor' => 'rgba(244,63,94,.08)',
                    'tension'         => 0.4,
                    'fill'            => false,
                    'pointRadius'     => 4,
                ],
                [
                    'label'           => 'Komentar',
                    'data'            => $comments,
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245,158,11,.08)',
                    'tension'         => 0.4,
                    'fill'            => false,
                    'pointRadius'     => 4,
                ],
                [
                    'label'           => 'Reach',
                    'data'            => $reach,
                    'borderColor'     => '#14c8aa',
                    'backgroundColor' => 'rgba(20,200,170,.12)',
                    'tension'         => 0.4,
                    'fill'            => true,
                    'pointRadius'     => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'grid' => ['color' => 'rgba(255,255,255,.05)']],
                'x' => ['grid' => ['display' => false]],
            ],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }
}
