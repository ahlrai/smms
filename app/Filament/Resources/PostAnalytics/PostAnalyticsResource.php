<?php

namespace App\Filament\Resources\PostAnalytics;

use App\Filament\Resources\PostAnalytics\Pages\CreatePostAnalytics;
use App\Filament\Resources\PostAnalytics\Pages\EditPostAnalytics;
use App\Filament\Resources\PostAnalytics\Pages\ListPostAnalytics;
use App\Filament\Resources\PostAnalytics\Pages\ViewPostAnalytics;
use App\Filament\Resources\PostAnalytics\Schemas\PostAnalyticsForm;
use App\Filament\Resources\PostAnalytics\Schemas\PostAnalyticsInfolist;
use App\Filament\Resources\PostAnalytics\Tables\PostAnalyticsTable;
use App\Models\PostAnalytics;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Metric;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PostAnalyticsResource extends Resource
{
    protected static ?string $model = Metric::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?string $navigationLabel = 'Post Analytics';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermissionTo('analytics.view') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $latestMetrics = Metric::query()
            ->selectRaw('post_id, platform, MAX(recorded_date) as latest_date')
            ->groupBy('post_id', 'platform');

        return parent::getEloquentQuery()
            ->joinSub($latestMetrics, 'latest', function ($join) {
                $join->on('metrics.post_id', '=', 'latest.post_id')
                    ->on('metrics.platform', '=', 'latest.platform')
                    ->on('metrics.recorded_date', '=', 'latest.latest_date');
            })
            ->with(['post', 'socialAccount',]);       
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('reach', 'desc')

            ->columns([

                TextColumn::make('post.title')
                    ->label('Post')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                TextColumn::make('platform')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'facebook' => 'primary',
                        'instagram' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('socialAccount.username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('likes')
                    ->sortable()
                    ->numeric(),

                TextColumn::make('comments')
                    ->sortable()
                    ->numeric(),

                TextColumn::make('shares')
                    ->sortable()
                    ->numeric(),


                TextColumn::make('engagement')
                    ->label('Engagement')
                    ->state(fn ($record) =>
                        ($record->likes ?? 0)
                        + ($record->comments ?? 0)
                        + ($record->shares ?? 0)
                        + ($record->saves ?? 0)
                    )
                    ->sortable(false),

                TextColumn::make('recorded_date')
                    ->label('Last Sync')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('view_post')
                    ->label('Link')
                    ->state('View Post')
                    ->badge()
                    ->color('success')
                    ->url(function ($record) {
                        return $record->post?->socialAccounts()
                            ->where('platform', $record->platform)
                            ->value('post_url');
                    })
                 ->openUrlInNewTab()
            ])

            ->filters([

                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'instagram' => 'Instagram',
                        'facebook' => 'Facebook',
                    ]),
                SelectFilter::make('social_account_id')
                    ->label('Akun Sosial')
                    ->relationship(
                        'socialAccount',
                        'username'
                    )
                    ->searchable()
                    ->preload(),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostAnalytics::route('/'),
        ];
    }
}