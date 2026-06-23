<?php

namespace App\Filament\Resources\Messages;

use App\Filament\Resources\Messages\Pages;
use App\Models\Message;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MessageResource extends Resource
{
    protected static ?string $model                          = Message::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup  = 'Social Media';
    protected static bool $shouldRegisterNavigation          = false; // Digantikan UnifiedInboxPage
    protected static ?string $navigationLabel                = 'Pesan Masuk';
    protected static ?int    $navigationSort                 = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermissionTo('message.view') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Message::where('is_read', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('sender_username')->label('Pengirim')->disabled(),
            TextInput::make('platform')->label('Platform')->disabled(),
            Textarea::make('message')->label('Pesan')->disabled()->rows(4),
            Select::make('status')
                ->label('Status')
                ->options([
                    'new'       => 'Baru',
                    'follow-up' => 'Follow Up',
                    'resolved'  => 'Selesai',
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_username')
                    ->label('Pengirim')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'facebook'  => 'primary',
                        'instagram' => 'pink',
                        default     => 'gray',
                    }),

                TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'new'       => 'info',
                        'follow-up' => 'warning',
                        'resolved'  => 'success',
                        default     => 'gray',
                    }),

                IconColumn::make('is_read')
                    ->label('Dibaca')
                    ->boolean(),

                TextColumn::make('sent_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                SelectFilter::make('platform')->native(false)
                    ->options(['facebook' => 'Facebook', 'instagram' => 'Instagram']),

                SelectFilter::make('status')->native(false)
                    ->options([
                        'new'       => 'Baru',
                        'follow-up' => 'Follow Up',
                        'resolved'  => 'Selesai',
                    ]),

                TernaryFilter::make('is_read')->label('Sudah Dibaca'),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('reply')
                    ->label('Balas')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->hasPermissionTo('message.reply') ?? false)
                    ->form([
                        Textarea::make('reply')
                            ->label('Pesan Balasan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Message $record, array $data) {
                        $record->replies()->create([
                            'reply'      => $data['reply'],
                            'replied_by' => auth()->id(),
                        ]);
                        $record->markAsRead();
                        $record->markAsFollowUp();

                        Notification::make()
                            ->title('Balasan berhasil dikirim!')
                            ->success()
                            ->send();
                    }),

                Action::make('mark_resolved')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Message $record) => $record->status !== 'resolved')
                    ->action(function (Message $record) {
                        $record->markAsResolved();
                        Notification::make()->title('Ditandai selesai!')->success()->send();
                    }),

                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
        ];
    }
}