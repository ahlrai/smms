<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class CalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.calendar-widget';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    /**
     * Auto refresh widget setiap 10 detik
     */
    protected static ?string $pollingInterval = '10s';

    public int $year;
    public int $month;

    public ?string $selectedDate = null;

    public bool $showModal = false;

    public function mount(): void
    {
        $this->year  = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::createFromDate(
            $this->year,
            $this->month,
            1
        )->subMonth();

        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromDate(
            $this->year,
            $this->month,
            1
        )->addMonth();

        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->showModal    = true;
    }

    public function closeModal(): void
    {
        $this->showModal    = false;
        $this->selectedDate = null;
    }

    public function getSelectedPosts()
{
    if (!$this->selectedDate) {
        return collect();
    }

    return Post::query()
    ->where(function ($query) {

        $query->where(function ($q) {
            $q->where('status', 'scheduled')
              ->whereDate('scheduled_at', $this->selectedDate);
        });

        $query->orWhere(function ($q) {
            $q->where('status', 'published')
              ->whereDate('published_at', $this->selectedDate);
        });

        $query->orWhere(function ($q) {
            $q->where('status', 'failed')
              ->whereDate('scheduled_at', $this->selectedDate);
        });

    })
    ->with('socialAccounts')
    ->orderBy('scheduled_at')
    ->get();
}

    public function getCalendarData(): array
    {
        $firstDay = Carbon::createFromDate(
            $this->year,
            $this->month,
            1
        );

        $lastDay = $firstDay
            ->copy()
            ->endOfMonth();

        $monthName = $firstDay
            ->locale('id')
            ->translatedFormat('F Y');

        $daysInMonth = $lastDay->day;

        $startDow = $firstDay->dayOfWeek;

        /*
        |--------------------------------------------------------------------------
        | Ambil semua post bulan ini
        |--------------------------------------------------------------------------
        */

        $posts = Post::where(function ($query) use (
            $firstDay,
            $lastDay
        ) {
            $query
                ->whereBetween(
                    'scheduled_at',
                    [
                        $firstDay->copy()->startOfDay(),
                        $lastDay->copy()->endOfDay(),
                    ]
                )
                ->orWhereBetween(
                    'published_at',
                    [
                        $firstDay->copy()->startOfDay(),
                        $lastDay->copy()->endOfDay(),
                    ]
                );
        })
        ->whereIn('status', [
            'scheduled',
            'published',
            'failed',
        ])
        ->with('socialAccounts')
        ->get()
        ->groupBy(function ($post) {

    $date = $post->status === 'scheduled'
        ? $post->scheduled_at
        : ($post->published_at ?? $post->scheduled_at);

    return Carbon::parse($date)->format('Y-m-d');
});

        $grid = [];

        $dayNum = 1;

        for ($row = 0; $row < 6; $row++) {

            $week = [];

            for ($col = 0; $col < 7; $col++) {

                $cellIndex = ($row * 7) + $col;

                if (
                    $cellIndex < $startDow ||
                    $dayNum > $daysInMonth
                ) {
                    $week[] = null;

                    continue;
                }

                $dateKey = Carbon::createFromDate(
                    $this->year,
                    $this->month,
                    $dayNum
                )->format('Y-m-d');

                $week[] = [
                    'day'   => $dayNum,
                    'date'  => $dateKey,
                    'posts' => $posts[$dateKey]
                        ?? collect(),
                    'today' => $dateKey === now()->toDateString(),
                ];

                $dayNum++;
            }

            $grid[] = $week;

            if ($dayNum > $daysInMonth) {
                break;
            }
        }

        return [
            'monthName' => $monthName,
            'grid'      => $grid,
        ];
    }

    /**
     * Dipanggil otomatis oleh polling Livewire
     */
    public function refreshCalendar(): void
    {
        // kosongkan saja
        // Livewire akan rerender widget otomatis
    }
}