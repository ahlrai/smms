<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages;
use App\Jobs\PublishPostJob;
use App\Models\Post;
use App\Models\SocialAccount;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

use Filament\Notifications\Notification;

use Illuminate\Support\Facades\Auth;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Social Media';

    protected static ?string $navigationLabel = 'Manajemen Post';

    protected static ?int $navigationSort = 2;

    /*
    |--------------------------------------------------------------------------
    | AUTO CREATED BY
    |--------------------------------------------------------------------------
    */

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT
            |--------------------------------------------------------------------------
            */

            Select::make('social_account_id')
                ->label('Akun Sosial')
                ->options(
                    SocialAccount::all()->pluck('username', 'id')
                )
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(
                    fn ($state, callable $set) =>
                    $set(
                        'platform',
                        SocialAccount::find($state)?->platform
                    )
                ),

            /*
            |--------------------------------------------------------------------------
            | PLATFORM
            |--------------------------------------------------------------------------
            */

            Select::make('platform')
            ->label('Platform')
            ->options([
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            ])
            ->required()
            ->dehydrated()
            ->disabled(),

            /*
            |--------------------------------------------------------------------------
            | CAPTION
            |--------------------------------------------------------------------------
            */

            Textarea::make('caption')
                ->label('Caption')
                ->required()
                ->maxLength(2200)
                ->rows(5)
                ->columnSpanFull(),

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'scheduled' => 'Scheduled',
                ])
                ->default('draft')
                ->required()
                ->live(),

            /*
            |--------------------------------------------------------------------------
            | SCHEDULE
            |--------------------------------------------------------------------------
            */

            DateTimePicker::make('scheduled_at')
                ->label('Jadwal Posting')
                ->seconds(false)
                ->visible(
                    fn ($get) =>
                    $get('status') === 'scheduled'
                ),

            /*
            |--------------------------------------------------------------------------
            | MEDIA
            |--------------------------------------------------------------------------
            */

            FileUpload::make('media')
            ->label('Upload Media (Gambar/Video)')
            ->multiple()
            ->image()
            ->directory('post-media')
            ->columnSpanFull(),

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table

            ->columns([

                /*
                |--------------------------------------------------------------------------
                | CAPTION
                |--------------------------------------------------------------------------
                */

                TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(60)
                    ->searchable(),

                /*
                |--------------------------------------------------------------------------
                | PLATFORM
                |--------------------------------------------------------------------------
                */

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(
                        fn (string $state) => match ($state) {

                            'facebook' => 'primary',

                            'instagram' => 'danger',

                            default => 'gray',
                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(
                        fn (string $state) => match ($state) {

                            'published' => 'success',

                            'scheduled' => 'warning',

                            'failed' => 'danger',

                            default => 'gray',
                        }
                    ),

                /*
                |--------------------------------------------------------------------------
                | ACCOUNT
                |--------------------------------------------------------------------------
                */

                TextColumn::make('socialAccount.username')
                    ->label('Akun')
                    ->searchable(),

                /*
                |--------------------------------------------------------------------------
                | SCHEDULE
                |--------------------------------------------------------------------------
                */

                TextColumn::make('scheduled_at')
                    ->label('Jadwal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                /*
                |--------------------------------------------------------------------------
                | PUBLISHED
                |--------------------------------------------------------------------------
                */

                TextColumn::make('published_at')
                    ->label('Dipublish')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                /*
                |--------------------------------------------------------------------------
                | CREATED
                |--------------------------------------------------------------------------
                */

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),

            ])

            /*
            |--------------------------------------------------------------------------
            | FILTERS
            |--------------------------------------------------------------------------
            */

            ->filters([

                SelectFilter::make('status')
                    ->native(false)
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'published' => 'Published',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('platform')
                    ->native(false)
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                    ]),

            ])

            /*
            |--------------------------------------------------------------------------
            | ACTIONS
            |--------------------------------------------------------------------------
            */

            ->actions([

                /*
                |--------------------------------------------------------------------------
                | VIEW
                |--------------------------------------------------------------------------
                */

                ViewAction::make(),

                /*
                |--------------------------------------------------------------------------
                | PUBLISH
                |--------------------------------------------------------------------------
                */

                Action::make('publish')
                    ->label('Publish Sekarang')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish Post?')
                    ->modalDescription(
                        'Post akan dikirim ke platform melalui queue.'
                    )
                    ->visible(
                        fn (Post $record) =>
                        in_array(
                            $record->status,
                            ['draft', 'scheduled', 'failed']
                        )
                    )
                    ->action(function (Post $record) {

                        PublishPostJob::dispatch($record);

                        Notification::make()
                            ->title('Post masuk queue publish!')
                            ->body(
                                'Post akan segera dipublish ke '
                                . ucfirst($record->platform)
                            )
                            ->success()
                            ->send();
                    }),

                /*
                |--------------------------------------------------------------------------
                | EDIT
                |--------------------------------------------------------------------------
                */

                EditAction::make()
                ->visible(fn (Post $record) => $record->status !== 'published'),

                /*
                |--------------------------------------------------------------------------
                | DELETE
                |--------------------------------------------------------------------------
                */

                DeleteAction::make(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListPosts::route('/'),

            'create' => Pages\CreatePost::route('/create'),

            'edit' => Pages\EditPost::route('/{record}/edit'),

        ];
    }
}