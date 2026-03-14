<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages;
use App\Jobs\PublishPostJob;
use App\Models\Post;
use App\Models\SocialAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PostResource extends Resource
{
    protected static ?string $model                          = Post::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';
    protected static string|\UnitEnum|null $navigationGroup  = 'Social Media';
    protected static ?string $navigationLabel                = 'Manajemen Post';
    protected static ?int    $navigationSort                 = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('social_account_id')
                ->label('Akun Sosial')
                ->options(SocialAccount::all()->pluck('username', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('platform', SocialAccount::find($state)?->platform)
                ),

            Select::make('platform')
                ->label('Platform')
                ->options(['facebook' => 'Facebook', 'instagram' => 'Instagram'])
                ->required()
                ->disabled(),

            Textarea::make('caption')
                ->label('Caption')
                ->required()
                ->maxLength(2200)
                ->rows(5)
                ->columnSpanFull(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft'     => 'Draft',
                    'scheduled' => 'Scheduled',
                ])
                ->default('draft')
                ->required()
                ->live(),

            DateTimePicker::make('scheduled_at')
                ->label('Jadwal Posting')
                ->visible(fn ($get) => $get('status') === 'scheduled'),

            FileUpload::make('media')
                ->label('Upload Media (Gambar/Video)')
                ->multiple()
                ->image()
                ->directory('post-media')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'facebook'  => 'primary',
                        'instagram' => 'pink',
                        default     => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'warning',
                        'failed'    => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('socialAccount.username')
                    ->label('Akun')
                    ->searchable(),

                TextColumn::make('scheduled_at')
                    ->label('Jadwal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Dipublish')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->native(false)
                    ->options([
                        'draft'     => 'Draft',
                        'scheduled' => 'Scheduled',
                        'published' => 'Published',
                        'failed'    => 'Failed',
                    ]),

                SelectFilter::make('platform')->native(false)
                    ->options([
                        'facebook'  => 'Facebook',
                        'instagram' => 'Instagram',
                    ]),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('publish')
                    ->label('Publish Sekarang')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish Post?')
                    ->modalDescription('Post akan dikirim ke platform melalui antrian. Status akan diperbarui otomatis.')
                    ->visible(fn (Post $record) => in_array($record->status, ['draft', 'scheduled', 'failed']))
                    ->action(function (Post $record) {
                        // Dispatch ke queue agar publish via API secara async
                        PublishPostJob::dispatch($record);

                        Notification::make()
                            ->title('Post masuk antrian publish!')
                            ->body('Post akan dikirim ke ' . ucfirst($record->platform) . ' segera.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}