<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages;
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
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

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

            if (strtolower($acc->platform) !== 'instagram') {
                continue;
            }

            $token = $acc->access_token;

            $igId = $acc->account_id;

            $allMedia = $post->media;

            if (empty($allMedia)) {

                return [
                    'success' => false,
                    'message' => 'Media tidak ditemukan',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | CLOUDINARY
            |--------------------------------------------------------------------------
            */

            $cloudinary = new Cloudinary([

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

$filePath =
    public_path('storage/' . $media);

$mimeType =
    mime_content_type($filePath);

$isVideo =
    str_starts_with($mimeType, 'video/');

$filePath = public_path('storage/' . $media);

$upload =
    $cloudinary->uploadApi()->upload(
        $filePath,
        [
            'resource_type' =>
                $isVideo
                    ? 'video'
                    : 'image',
        ]
    );

                $uploadedFileUrl =
                    $upload['secure_url'];

                $container = Http::post(
    "https://graph.facebook.com/v22.0/$igId/media",

    $isVideo
        ? [
            'media_type' => 'REELS',

            'video_url' =>
            $uploadedFileUrl,

            'caption' =>
            $post->caption,

            'access_token' =>
            $token,
        ]
        : [
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
                        $container['error']['message'],

                    ];
                }

                $containerId = $container['id'];

if ($isVideo) {

    $status = null;

    for ($i = 0; $i < 12; $i++) {

        sleep(10);

        $statusResponse = Http::get(
            "https://graph.facebook.com/v22.0/{$containerId}",
            [
                'fields' => 'status_code',
                'access_token' => $token,
            ]
        )->json();

        $status = $statusResponse['status_code'] ?? null;

        if ($status === 'FINISHED') {
            break;
        }
    }

    if ($status !== 'FINISHED') {

        return [
            'success' => false,
            'message' => 'Instagram masih memproses video.',
        ];
    }
}

$publish = Http::post(

    "https://graph.facebook.com/v22.0/$igId/media_publish",

    [

        'creation_id' => $containerId,

        'access_token' => $token,

    ]

)->json();

                if (isset($publish['error'])) {

                    return [

                        'success' => false,

                        'message' =>
                        $publish['error']['message'],

                    ];
                }

                $postId = $publish['id'] ?? null;

                $postUrl = null;

                if ($postId) {

                    $mediaInfo = Http::get(
                        "https://graph.facebook.com/v22.0/{$postId}",
                        [
                            'fields' => 'permalink',
                            'access_token' => $token,
                        ]
                    )->json();

                    $postUrl = $mediaInfo['permalink'] ?? null;
                }

                $post->update([

                    'instagram_post_id' => $postId,

                    'post_url' => $postUrl,

                ]);

                return [

                    'success' => true,

                    'post_id' => $postId,

                    'post_url' => $postUrl,

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

                $filePath =
    public_path('storage/' . $media);

$mimeType =
    mime_content_type($filePath);

$isVideo =
    str_starts_with($mimeType, 'video/');

$filePath = public_path('storage/' . $media);

$upload =
    $cloudinary->uploadApi()->upload(
        $filePath,
        [
            'resource_type' =>
                $isVideo
                    ? 'video'
                    : 'image',
        ]
    );

                $uploadedFileUrl =
                    $upload['secure_url'];

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
                        $child['error']['message'],

                    ];
                }

                $children[] = $child['id'];
            }

            

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
                    $carousel['error']['message'],

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
                    $publish['error']['message'],

                ];
            }

            $postId = $publish['id'] ?? null;

/*
|--------------------------------------------------------------------------
| AMBIL POST TERBARU
|--------------------------------------------------------------------------
*/

sleep(3);

$latestPostResponse = Http::get(

    "https://graph.facebook.com/v22.0/{$igId}/media",

    [

        'fields' => 'id,permalink',

        'limit' => 1,

        'access_token' => $token,

    ]

);

\Log::info('LATEST POST RESPONSE');

\Log::info($latestPostResponse->body());

$latestPost =
    $latestPostResponse->json();

\Log::info($latestPost);

$postUrl =
    $latestPost['data'][0]['permalink']
    ?? null;

$post->update([

    'instagram_post_id' => $postId,

    'post_url' => $postUrl,

]);

            return [

                'success' => true,

                'post_id' => $postId,

                'post_url' => $postUrl,

            ];
        }

        return [

            'success' => false,

            'message' =>
            'Akun instagram tidak ditemukan',

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
                            $account->id =>
                            strtoupper($account->platform)
                                . ' - '
                                . $account->username,
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

                    'video/mp4',

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

                TextColumn::make('post_url')
                    ->label('URL Post')
                    ->url(fn($record) => $record->post_url)
                    ->openUrlInNewTab()
                    ->limit(30),

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

                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')

                    ->modalHeading('Preview Postingan')

                    ->modalWidth('4xl')

                    ->modalContent(function (Post $record) {

                        $media = $record->media[0] ?? null;

                        $imageUrl = $media
                            ? asset('storage/' . $media)
                            : null;

                        $isVideo =
                        $media &&
                        str_ends_with(
                            strtolower($media),
                            '.mp4'
                        );

                        return new HtmlString('

                        <div style="
                            max-width:400px;
                            margin:auto;
                            border:1px solid #dbdbdb;
                            border-radius:12px;
                            overflow:hidden;
                            background:#ffffff;
                            font-family:Arial,sans-serif;
                        ">

                            ' .

                            (
    $imageUrl
        ? (
            $isVideo

                ? '<video controls
                        style="
                            width:100%;
                            display:block;
                        ">
                        <source src="' . $imageUrl . '" type="video/mp4">
                   </video>'

                : '<img
                        src="' . $imageUrl . '"
                        style="
                            width:100%;
                            display:block;
                        "
                   >'
        )
        : ''
)

                            . '

                            <div style="
                                padding:12px;
                                font-size:14px;
                                line-height:1.5;
                            ">

                                ' . nl2br(e($record->caption)) . '

                            </div>

                        </div>

                        ');
                    }),

                Action::make('publish')
                    ->label('Publish Sekarang')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish Post?')
                    ->modalDescription(
                        'Post akan dikirim ke platform.'
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
                    ->visible(
                        fn(Post $record) =>
                        $record->status !== 'published'
                    ),

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