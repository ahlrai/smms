<?php

namespace App\Filament\Resources\Comments;

use App\Filament\Resources\Comments\Pages;
use App\Models\Comment;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CommentResource extends Resource
{
    protected static ?string $model                          = Comment::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static string|\UnitEnum|null $navigationGroup  = 'Social Media';
    protected static ?string $navigationLabel                = 'Komentar';
    protected static ?int    $navigationSort                 = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermissionTo('comment.view') ?? false;
    }

    // Comments are synced from platforms; manual creation is not allowed.
    public static function canCreate(): bool
    {
        return false;
    }

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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['replies.replier']))
            ->columns([
                TextColumn::make('commenter_username')
                    ->label('')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('content')
                    ->label('')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('thread')
                    ->label('Komentar')
                    ->html()
                    ->getStateUsing(fn (Comment $record) => view(
                        'filament.comments.inline-thread',
                        ['comment' => $record]
                    )->render())
                    ->wrap()
                    ->grow(true),

                TextColumn::make('post.caption')
                    ->label('Post')
                    ->limit(30)
                    ->wrap()
                    ->searchable()
                    ->grow(false),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'facebook'  => 'primary',
                        'instagram' => 'pink',
                        default     => 'gray',
                    })
                    ->grow(false),

                TextColumn::make('commented_at')
                    ->label('Waktu')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->grow(false),
            ])
            ->defaultSort('commented_at', 'desc')
            ->filters([
                SelectFilter::make('platform')->native(false)
                    ->options(['facebook' => 'Facebook', 'instagram' => 'Instagram']),

                TernaryFilter::make('is_replied')->label('Sudah Dibalas'),
            ])
            ->actions([
                Action::make('reply')
                    ->label('Balas')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->hasPermissionTo('comment.reply') ?? false)
                    ->modalHeading(fn (Comment $record) => 'Balas komentar @' . $record->commenter_username)
                    ->modalDescription(fn (Comment $record) => 'Balasan akan dikirim langsung ke ' . ucfirst($record->platform) . '.')
                    ->form([
                        Textarea::make('reply')
                            ->label('Balasan Komentar')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Comment $record, array $data) {
                        $dbReply = $record->replies()->create([
                            'reply'      => $data['reply'],
                            'replied_by' => auth()->id(),
                        ]);

                        if (!$record->platform_comment_id || !$record->socialAccount) {
                            $record->markAsReplied();
                            Notification::make()
                                ->title('Balasan disimpan, tidak dapat dikirim ke platform')
                                ->body('Data akun atau ID komentar platform tidak ditemukan.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $platformReplyId = null;
                        $error           = null;

                        try {
                            $result = match ($record->platform) {
                                'instagram' => app(InstagramService::class)->replyComment(
                                    $record->socialAccount,
                                    $record->platform_comment_id,
                                    $data['reply']
                                ),
                                'facebook'  => app(FacebookService::class)->replyComment(
                                    $record->socialAccount,
                                    $record->platform_comment_id,
                                    $data['reply']
                                ),
                                default => [],
                            };

                            if (isset($result['id'])) {
                                $platformReplyId = $result['id'];
                            } else {
                                $error = $result['error']['message'] ?? 'Gagal mengirim ke ' . ucfirst($record->platform);
                            }
                        } catch (\Throwable $e) {
                            $error = $e->getMessage();
                        }

                        if ($platformReplyId) {
                            $dbReply->markAsSent($platformReplyId);
                            Notification::make()
                                ->title('Balasan berhasil dikirim ke ' . ucfirst($record->platform) . '!')
                                ->success()
                                ->send();
                        } else {
                            $record->markAsReplied();
                            Notification::make()
                                ->title('Balasan tersimpan, gagal dikirim ke platform')
                                ->body($error)
                                ->warning()
                                ->send();
                        }
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
