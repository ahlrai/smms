<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages;
use App\Jobs\PublishPostJob;
use App\Models\Post;
use App\Models\SocialAccount;

use Cloudinary\Cloudinary;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

use Filament\Notifications\Notification;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
    | PUBLISH INSTAGRAM
    |--------------------------------------------------------------------------
    */

    public static function publishInstagram(Post $post)
    {
        foreach ($post->socialAccounts as $acc) {

            if (
                strtolower($acc->platform)
                !== 'instagram'
            ) {
                continue;
            }

            $token = $acc->access_token;

            $igId = $acc->account_id;

            /*
            |--------------------------------------------------------------------------
            | AMBIL SEMUA MEDIA
            |--------------------------------------------------------------------------
            */

            $allMedia = $post->media;

            if (empty($allMedia)) {

                return [
                    'success' => false,
                    'message' => 'Media tidak ditemukan'
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | CLOUDINARY
            |--------------------------------------------------------------------------
            */

            $cloudinary = new \Cloudinary\Cloudinary([

                'cloud' => [

                    'cloud_name' =>
                    env('CLOUDINARY_CLOUD_NAME'),

                    'api_key' =>
                    env('CLOUDINARY_API_KEY'),

                    'api_secret' =>
                    env('CLOUDINARY_API_SECRET'),

                ],

                'url' => [
                    'secure' => true,
                ],

            ]);

            /*
            |--------------------------------------------------------------------------
            | SINGLE IMAGE
            |--------------------------------------------------------------------------
            */

            if (count($allMedia) === 1) {

                $media =
                    str_replace(
                        'storage/',
                        '',
                        $allMedia[0]
                    );

                $upload =
                    $cloudinary->uploadApi()->upload(
                        public_path('storage/' . $media)
                    );

                $uploadedFileUrl =
                    $upload['secure_url'];

                $container = Http::post(

                    "https://graph.facebook.com/v22.0/$igId/media",

                    [

                        'image_url' =>
                        $uploadedFileUrl,

                        'caption' =>
                        $post->caption,

                        'access_token' =>
                        $token,

                    ]

                )->json();

                if (isset($container['error'])) {

                    return [

                        'success' => false,

                        'message' =>
                        $container['error']['message']

                    ];
                }

                sleep(5);

                $publish = Http::post(

                    "https://graph.facebook.com/v22.0/$igId/media_publish",

                    [

                        'creation_id' =>
                        $container['id'],

                        'access_token' =>
                        $token,

                    ]

                )->json();

                if (isset($publish['error'])) {

                    return [

                        'success' => false,

                        'message' =>
                        $publish['error']['message']

                    ];
                }

                return [
                    'success' => true
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | CAROUSEL
            |--------------------------------------------------------------------------
            */

            $children = [];

            foreach ($allMedia as $media) {

                $media =
                    str_replace(
                        'storage/',
                        '',
                        $media
                    );

                $upload =
                    $cloudinary->uploadApi()->upload(
                        public_path('storage/' . $media)
                    );

                $uploadedFileUrl =
                    $upload['secure_url'];

                /*
                |--------------------------------------------------------------------------
                | CHILD CONTAINER
                |--------------------------------------------------------------------------
                */

                $child = Http::post(

                    "https://graph.facebook.com/v22.0/$igId/media",

                    [

                        'image_url' =>
                        $uploadedFileUrl,

                        'is_carousel_item' =>
                        true,

                        'access_token' =>
                        $token,

                    ]

                )->json();

                if (isset($child['error'])) {

                    return [

                        'success' => false,

                        'message' =>
                        $child['error']['message']

                    ];
                }

                $children[] = $child['id'];
            }

            sleep(5);

            /*
            |--------------------------------------------------------------------------
            | CAROUSEL CONTAINER
            |--------------------------------------------------------------------------
            */

            $carousel = Http::post(

                "https://graph.facebook.com/v22.0/$igId/media",

                [

                    'media_type' => 'CAROUSEL',

                    'children' => implode(',', $children),

                    'caption' => $post->caption,

                    'access_token' => $token,

                ]

            )->json();

            if (isset($carousel['error'])) {

                return [

                    'success' => false,

                    'message' =>
                    $carousel['error']['message']

                ];
            }

            sleep(5);

            /*
            |--------------------------------------------------------------------------
            | PUBLISH CAROUSEL
            |--------------------------------------------------------------------------
            */

            $publish = Http::post(

                "https://graph.facebook.com/v22.0/$igId/media_publish",

                [

                    'creation_id' =>
                    $carousel['id'],

                    'access_token' =>
                    $token,

                ]

            )->json();

            if (isset($publish['error'])) {

                return [

                    'success' => false,

                    'message' =>
                    $publish['error']['message']

                ];
            }

            return [
                'success' => true
            ];
        }

        return [

            'success' => false,

            'message' =>
            'Akun instagram tidak ditemukan'

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Select::make('social_account_ids')
                ->label('Akun Sosial')
                ->multiple()
                ->options(
                    SocialAccount::all()->mapWithKeys(function ($account) {
                        return [
                            $account->id => strtoupper($account->platform) . ' - ' . $account->username
                        ];
                    })
                )
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {

                    $platforms = SocialAccount::whereIn('id', $state ?? [])
                        ->pluck('platform')
                        ->unique()
                        ->implode(', ');

                    $set('platform', $platforms);
                }),

            Textarea::make('platform')
                ->label('Platform')
                ->disabled()
                ->dehydrated()
                ->rows(1),

            TextInput::make('title')
                ->label('Judul Postingan')
                ->required()
                ->default('Untitled Post')
                ->maxLength(255),

            Textarea::make('caption')
                ->label('Caption')
                ->required()
                ->maxLength(2200)
                ->rows(5)
                ->columnSpanFull(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'scheduled' => 'Scheduled',
                ])
                ->default('draft')
                ->required()
                ->live(),

            DateTimePicker::make('scheduled_at')
                ->label('Jadwal Posting')
                ->seconds(false)
                ->visible(
                    fn($get) =>
                    $get('status') === 'scheduled'
                ),

            FileUpload::make('media')

                ->label('Upload Media')

                ->multiple()

                ->disk('public')

                ->directory('post-media')

                ->visibility('public')

                ->imagePreviewHeight('200')

                ->panelLayout('grid')

                ->preserveFilenames()

                ->openable()

                ->downloadable()

                ->reorderable()

                ->appendFiles()

                ->acceptedFileTypes([

                    'image/jpeg',

                    'image/png',

                    'video/mp4'

                ])

                ->getUploadedFileNameForStorageUsing(
                    fn($file) =>

                    time()
                        . '_'
                        .
                        Str::random(10)
                        .
                        '.'
                        .
                        $file->getClientOriginalExtension()

                )

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

                TextColumn::make('title')
                    ->label('Judul Postingan')
                    ->formatStateUsing(function ($state, $record) {

                        return $state
                            ?: Str::words($record->caption, 5, '...');
                    })
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(
                        fn(string $state) => match ($state) {

                            'facebook' => 'primary',

                            'instagram' => 'danger',

                            default => 'gray',
                        }
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(
                        fn(string $state) => match ($state) {

                            'published' => 'success',

                            'scheduled' => 'warning',

                            'failed' => 'danger',

                            default => 'gray',
                        }
                    ),

                TextColumn::make('socialAccounts.username')
                    ->label('Akun')
                    ->badge()
                    ->separator(', ')
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

            ->actions([

                ViewAction::make(),

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
                        fn(Post $record) =>
                        in_array(
                            $record->status,
                            ['draft', 'scheduled', 'failed']
                        )
                    )
                    ->action(function (Post $record) {

                        $result =
                            self::publishInstagram($record);

                        if ($result['success']) {

                            $record->update([

                                'status' => 'published',

                                'published_at' => now(),
                            ]);

                            Notification::make()

                                ->title(
                                    'Post berhasil dipublish'
                                )

                                ->success()

                                ->send();

                        } else {

                            $record->update([

                                'status' => 'failed',
                            ]);

                            Notification::make()

                                ->title(
                                    'Publish gagal'
                                )

                                ->body(
                                    $result['message']
                                )

                                ->danger()

                                ->send();
                        }
                    }),

                EditAction::make()
                    ->visible(fn(Post $record) => $record->status !== 'published'),

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