<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class CalendarWidget extends Widget
{
    protected string $view                     = 'filament.widgets.calendar-widget';
    protected static ?int $sort                = 6;
    protected int | string | array $columnSpan = 'full';

    public int $year;
    public int $month;

    // State untuk modal detail
    public ?string $selectedDate  = null;
    public bool    $showModal     = false;

    public function mount(): void
    {
        $this->year  = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date        = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date        = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year  = $date->year;
        $this->month = $date->month;
    }

    // Dipanggil saat user klik tanggal
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

    // Post untuk tanggal yang dipilih
    public function getSelectedPosts(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->selectedDate) return collect();

        return Post::whereDate('scheduled_at', $this->selectedDate)
            ->orWhereDate('published_at', $this->selectedDate)
            ->with(['socialAccount', 'media'])
            ->orderBy('scheduled_at')
            ->get();
    }

    public function getCalendarData(): array
    {
        $firstDay    = Carbon::createFromDate($this->year, $this->month, 1);
        $lastDay     = $firstDay->copy()->endOfMonth();
        $monthName   = $firstDay->locale('id')->translatedFormat('F Y');
        $daysInMonth = $lastDay->day;
        $startDow    = $firstDay->dayOfWeek;

        $posts = Post::whereBetween('scheduled_at', [
                $firstDay->copy()->startOfDay(),
                $lastDay->copy()->endOfDay(),
            ])
            ->whereIn('status', ['scheduled', 'published', 'failed'])
            ->with('socialAccount')
            ->get()
            ->groupBy(fn ($p) => Carbon::parse($p->scheduled_at)->format('Y-m-d'));

        $grid   = [];
        $dayNum = 1;

        for ($row = 0; $row < 6; $row++) {
            $week = [];
            for ($col = 0; $col < 7; $col++) {
                $cellIndex = $row * 7 + $col;
                if ($cellIndex < $startDow || $dayNum > $daysInMonth) {
                    $week[] = null;
                } else {
                    $dateKey = Carbon::createFromDate($this->year, $this->month, $dayNum)->format('Y-m-d');
                    $week[]  = [
                        'day'   => $dayNum,
                        'date'  => $dateKey,
                        'posts' => $posts[$dateKey] ?? collect(),
                        'today' => $dateKey === now()->toDateString(),
                    ];
                    $dayNum++;
                }
            }
            $grid[] = $week;
            if ($dayNum > $daysInMonth) break;
        }

        return [
            'monthName' => $monthName,
            'grid'      => $grid,
        ];
    }
}
