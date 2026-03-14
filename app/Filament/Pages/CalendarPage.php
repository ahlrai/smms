<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CalendarWidget;
use Filament\Pages\Page;

class CalendarPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup  = 'Social Media';
    protected static ?string $navigationLabel                = 'Kalender Post';
    protected static ?int    $navigationSort                 = 5;
    protected string $view = 'filament.pages.calendar-page';

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
