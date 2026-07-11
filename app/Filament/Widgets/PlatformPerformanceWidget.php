<?php

namespace App\Filament\Widgets;

use App\Models\Metric;
use App\Models\SocialAccount;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

class PlatformPerformanceWidget extends Widget implements HasForms
{
    use InteractsWithForms;    
    protected string $view = 'filament.widgets.platform-performance-widget';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';
    public ?array $data = [];

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $socialAccountId = null;

    public static function canView(): bool
    {
        return auth()->user()?->hasPermissionTo('analytics.view') ?? false;
    }

    protected function getViewData(): array
    {
        $account = $this->socialAccountId
        ? SocialAccount::find($this->socialAccountId)
        : null;

    return [
        'facebook'      => $this->getPlatformData('facebook'),
        'instagram'     => $this->getPlatformData('instagram'),
        'showFacebook'  => ! $account || $account->platform === 'facebook',
        'showInstagram' => ! $account || $account->platform === 'instagram',
    ];
    }

    private function getPlatformData(string $platform): array
    {
        $query = $this->getMetricQuery($platform);

    return [
        'likes'       => (clone $query)->sum('likes'),
        'comments'    => (clone $query)->sum('comments'),
        'shares'      => (clone $query)->sum('shares'),
        'saves'       => (clone $query)->sum('saves'),
    ];
    }


private function getMetricQuery(string $platform)
{
    return Metric::query()
        ->when(
            $this->socialAccountId,
            fn ($q) => $q->where('social_account_id', $this->socialAccountId)
        )
        ->when(
            $this->startDate,
            fn ($q) => $q->whereDate('recorded_date', '>=', $this->startDate)
        )
        ->when(
            $this->endDate,
            fn ($q) => $q->whereDate('recorded_date', '<=', $this->endDate)
        )
        ->where('platform', $platform);
}

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            Select::make('socialAccountId')
                ->label('Akun Sosial')
                ->options(
                    SocialAccount::pluck('username', 'id')
                )
                ->searchable()
                ->placeholder('Semua Akun')
                ->live(),

            DatePicker::make('startDate')
                ->label('Dari Tanggal')
                ->live(),

            DatePicker::make('endDate')
                ->label('Sampai Tanggal')
                ->live(),
        ])
        ->columns(3);
}

public function mount(): void
{
    $this->startDate = now()->startOfMonth()->toDateString();
    $this->endDate   = now()->toDateString();

    $this->form->fill();
}

public function getSelectedAccount(): ?SocialAccount
{
    if (! $this->socialAccountId) {
        return null;
    }

    return SocialAccount::find($this->socialAccountId);
}
}