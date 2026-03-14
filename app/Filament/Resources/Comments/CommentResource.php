<?php

namespace App\Filament\Resources\Comments;

use App\Filament\Resources\Comments\Pages;
use App\Models\Comment;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CommentResource extends Resource
{
    protected static ?string $model                          = Comment::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static string|\UnitEnum|null $navigationGroup  = 'Social Media';
    protected static ?string $navigationLabel                = 'Komentar';
    protected static ?int    $navigationSort                 = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) Comment::where('is_replied', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commenter_username')
                    ->label('Pengguna')
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

                TextColumn::make('post.caption')
                    ->label('Post')
                    ->limit(40),

                TextColumn::make('content')
                    ->label('Komentar')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('like_count')
                    ->label('Likes')
                    ->sortable(),

                IconColumn::make('is_replied')
                    ->label('Dibalas')
                    ->boolean(),

                IconColumn::make('is_hidden')
                    ->label('Disembunyikan')
                    ->boolean(),

                TextColumn::make('commented_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('commented_at', 'desc')
            ->filters([
                SelectFilter::make('platform')->native(false)
                    ->options(['facebook' => 'Facebook', 'instagram' => 'Instagram']),

                TernaryFilter::make('is_replied')->label('Sudah Dibalas'),
                TernaryFilter::make('is_hidden')->label('Disembunyikan'),
            ])
            ->actions([
                Action::make('reply')
                    ->label('Balas')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->form([
                        Textarea::make('reply')
                            ->label('Balasan Komentar')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Comment $record, array $data) {
                        $record->replies()->create([
                            'reply'      => $data['reply'],
                            'replied_by' => auth()->id(),
                        ]);
                        $record->markAsReplied();

                        Notification::make()
                            ->title('Balasan berhasil dikirim!')
                            ->success()
                            ->send();
                    }),

                Action::make('toggle_hide')
                    ->label(fn (Comment $record) => $record->is_hidden ? 'Tampilkan' : 'Sembunyikan')
                    ->icon(fn (Comment $record) => $record->is_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color('warning')
                    ->action(function (Comment $record) {
                        $record->is_hidden ? $record->show() : $record->hide();
                        Notification::make()->title('Status komentar diperbarui!')->success()->send();
                    }),

                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
        ];
    }
}