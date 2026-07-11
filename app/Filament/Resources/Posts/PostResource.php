<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages;
use App\Jobs\FetchPostUrlJob;
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

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyPermission(['post.create', 'post.edit', 'post.delete', 'post.publish', 'post.schedule']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermissionTo('post.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasPermissionTo('post.edit') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasPermissionTo('post.delete') ?? false;
    }

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

\Log::info('POST ID');
\Log::info([$postId]);

$postUrl = null;

if ($postId) {

    sleep(5);

    $mediaInfo = Http::get(
        "https://graph.facebook.com/v22.0/{$postId}",
        [
            'fields' => 'id,permalink',
            'access_token' => $token,
        ]
    )->json();

    \Log::info('MEDIA INFO');
    \Log::info($mediaInfo);

    $postUrl = $mediaInfo['permalink'] ?? null;
}

\Log::info('POST URL');
\Log::info([$postUrl]);

                // Simpan ke pivot post_social_accounts (untuk sync komentar)
                $post->socialAccounts()->updateExistingPivot($acc->id, [
                    'platform_post_id' => $postId,
                    'post_url'         => $postUrl,
                ]);

                return [
                    'success'  => true,
                    'post_id'  => $postId,
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

            sleep(3);

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

            $postUrl = null;

if ($postId) {

    sleep(3);

    $mediaInfo = Http::get(
        "https://graph.facebook.com/v22.0/{$postId}",
        [
            'fields' => 'permalink',
            'access_token' => $token,
        ]
    )->json();

    \Log::info('IG Media Info', $mediaInfo);

    $postUrl = $mediaInfo['permalink'] ?? null;
}

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

$post->socialAccounts()
        ->updateExistingPivot(
        $acc->id,
        [
            'platform_post_id' => $postId,
            'post_url' => $postUrl,
        ]
    );

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
    | PUBLISH FACEBOOK
    |--------------------------------------------------------------------------
    */

    public static function publishFacebook(Post $post): array
    {
        foreach ($post->socialAccounts as $acc) {
            if (strtolower($acc->platform) !== 'facebook') {
                continue;
            }

            $token    = $acc->access_token;
            $pageId   = $acc->account_id;
            $allMedia = $post->media;

            // Text-only post (no media)
            if (empty($allMedia)) {
                $res = Http::post("https://graph.facebook.com/v22.0/{$pageId}/feed", [
                    'message'      => $post->caption,
                    'access_token' => $token,
                ])->json();

                if (isset($res['error'])) {
                    return ['success' => false, 'message' => $res['error']['message']];
                }

                $postId = $res['id'] ?? null;
                $post->socialAccounts()->updateExistingPivot($acc->id, [
                    'platform_post_id' => $postId,
                    'post_url'         => null,
                ]);

                return ['success' => true, 'post_id' => $postId];
            }

            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key'    => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
                'url' => ['secure' => true],
            ]);

            // Single image or video
            if (count($allMedia) === 1) {
                $media    = str_replace('storage/', '', $allMedia[0]);
                $filePath = public_path('storage/' . $media);
                $mimeType = mime_content_type($filePath);
                $isVideo  = str_starts_with($mimeType, 'video/');

                $upload = $cloudinary->uploadApi()->upload($filePath, [
                    'resource_type' => $isVideo ? 'video' : 'image',
                ]);
                $url = $upload['secure_url'];

                if ($isVideo) {
                    $res = Http::post("https://graph.facebook.com/v22.0/{$pageId}/videos", [
                        'file_url'     => $url,
                        'description'  => $post->caption,
                        'access_token' => $token,
                    ])->json();

                    if (isset($res['error'])) {
                        return ['success' => false, 'message' => $res['error']['message']];
                    }

                    $mediaId = $res['id'] ?? null;

                    // Poll Facebook video processing (max 2 min)
                    if ($mediaId) {
                        for ($i = 0; $i < 12; $i++) {
                            sleep(10);
                            $status = Http::get("https://graph.facebook.com/v22.0/{$mediaId}", [
                                'fields'       => 'status',
                                'access_token' => $token,
                            ])->json();
                            $videoStatus = $status['status']['video_status'] ?? null;
                            if ($videoStatus === 'ready' || $videoStatus === 'complete') break;
                        }
                    }
                } else {
                    $res = Http::post("https://graph.facebook.com/v22.0/{$pageId}/photos", [
                        'url'          => $url,
                        'caption'      => $post->caption,
                        'access_token' => $token,
                    ])->json();

                    if (isset($res['error'])) {
                        return ['success' => false, 'message' => $res['error']['message']];
                    }

                    // /photos returns both `id` (photo object) and `post_id` (feed post).
                    // We must store post_id — only feed post IDs support permalink_url in FetchPostUrlJob.
                    $mediaId = $res['post_id'] ?? $res['id'] ?? null;
                }

                $post->socialAccounts()->updateExistingPivot($acc->id, [
                    'platform_post_id' => $mediaId,
                ]);

                return ['success' => true, 'post_id' => $mediaId];
            }

            // Multiple images → upload each as unpublished, then create feed post
            $photoIds = [];
            foreach ($allMedia as $mediaItem) {
                $media    = str_replace('storage/', '', $mediaItem);
                $filePath = public_path('storage/' . $media);

                $upload = $cloudinary->uploadApi()->upload($filePath, ['resource_type' => 'image']);

                $photoRes = Http::post("https://graph.facebook.com/v22.0/{$pageId}/photos", [
                    'url'          => $upload['secure_url'],
                    'published'    => 'false',
                    'access_token' => $token,
                ])->json();

                if (isset($photoRes['error'])) {
                    return ['success' => false, 'message' => $photoRes['error']['message']];
                }

                $photoIds[] = $photoRes['id'];
            }

            $params = ['message' => $post->caption, 'access_token' => $token];
            foreach ($photoIds as $i => $pid) {
                $params["attached_media[{$i}]"] = json_encode(['media_fbid' => $pid]);
            }

            $feedRes = Http::asForm()->post("https://graph.facebook.com/v22.0/{$pageId}/feed", $params)->json();

            if (isset($feedRes['error'])) {
                return ['success' => false, 'message' => $feedRes['error']['message']];
            }

            $postId = $feedRes['id'] ?? null;

            $post->socialAccounts()->updateExistingPivot($acc->id, [
                'platform_post_id' => $postId,
                'post_url'         => null,
            ]);

            return ['success' => true, 'post_id' => $postId];

        }

        return ['success' => false, 'message' => 'Akun Facebook tidak ditemukan'];
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

                TextColumn::make('platform_urls')
                    ->label('URL Post')
                    ->getStateUsing(function (Post $record): string {
                        $links = $record->publishResults
                            ->filter(fn ($r) => $r->post_url)
                            ->map(function ($r) {
                                $platform = $r->socialAccount?->platform ?? 'unknown';
                                $label    = strtoupper($platform);
                                $color    = $platform === 'instagram' ? '#E1306C' : '#1877F2';
                                return "<a href=\"{$r->post_url}\" target=\"_blank\" "
                                    . "style=\"color:{$color};font-weight:600;margin-right:8px;\">"
                                    . "{$label} ↗</a>";
                            });

                        return $links->isNotEmpty() ? $links->implode(' ') : '—';
                    })
                    ->html(),

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
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')

    ->modalContent(function (Post $record) {

        $allMedia = $record->media ?? [];

        $mediaCount = count($allMedia);

        $slides = '';   

        foreach ($allMedia as $media) {

    $mediaUrl = asset('storage/' . $media);

    $isVideo = str_ends_with(
        strtolower($media),
        '.mp4'
    );

    if ($isVideo) {

        $slides .= '

            <video
                controls
                style="
                    width:100%;
                    display:block;
                    margin-bottom:10px;
                "
            >
                <source
                    src="' . $mediaUrl . '"
                    type="video/mp4">
            </video>

        ';

    } else {

        $slides .= '

            <img
                src="' . $mediaUrl . '"
                style="
                    width:100%;
                    display:block;
                    margin-bottom:10px;
                "
            >

        ';
    }
}

        $account =
            $record->socialAccounts->first();

        $username =
            $account?->username
            ?? 'instagram_user';

        $platforms =
           $record->socialAccounts
                ->pluck('platform')
                ->map(fn ($p) => strtolower($p))
                ->toArray();

        $instagramPreview = '
    isi html instagram
';

$facebookPreview = '
    <div style="
    max-width:430px;
    margin:auto;
    background:#ffffff;
    border:1px solid #ddd;
    border-radius:10px;
    overflow:hidden;
    font-family:Arial,sans-serif;
    color:#000;
">

    <div style="
        padding:12px;
        display:flex;
        align-items:center;
        gap:10px;
    ">

        <div style="
            width:40px;
            height:40px;
            border-radius:50%;
            background:#d9d9d9;
        ">
        </div>

        <div>

            <strong>'
                . e($username) .
            '</strong>

            <br>

            <span style="
                font-size:12px;
                color:#666;
            ">
                Baru saja
            </span>

        </div>

    </div>

    <img
        src="' . asset('storage/' . ($record->media[0] ?? '')) . '"
        style="
            width:100%;
            display:block;
        "
    >

    <div style="
        padding:12px;
        font-size:14px;
    ">
        '
        . nl2br(e($record->caption))
        .
    '
    </div>

    <div style="
        padding:12px;
        border-top:1px solid #eee;
        color:#666;
        font-size:13px;
    ">
        👍 120 Likes &nbsp;&nbsp; 💬 25 Komentar
    </div>

</div>
';

$html = '';

if (in_array('instagram', $platforms)) {

    $html .= '

    <h3 style="
        text-align:center;
        margin-bottom:10px;
    ">
        Preview Instagram
    </h3>

    ';

    $html .= $instagramPreview;
}

if (in_array('facebook', $platforms)) {

    $html .= '

    <div style="height:30px"></div>

    <h3 style="
        text-align:center;
        margin-bottom:10px;
    ">
        Preview Facebook
    </h3>

    ';

    $html .= $facebookPreview;
}
        return new HtmlString('

        <div style="
    max-width:430px;
    margin:auto;
    background:#ffffff;
    border:1px solid #dbdbdb;
    border-radius:10px;
    overflow:hidden;
    font-family:Arial,sans-serif;
    color:#000;
">

            <!-- HEADER -->

            <div style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                padding:12px;
            ">

                <div style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                ">

                    <div style="
                        width:35px;
                        height:35px;
                        border-radius:50%;
                        background:#d9d9d9;
                    ">
                    </div>

                    <strong>'
                        . e($username) .
                    '</strong>

                </div>

                <div style="
                    font-size:20px;
                    font-weight:bold;
                ">
                    •••
                </div>

            </div>

            <!-- MEDIA -->

<div style="
    width:100%;
">
    ' . $slides . '
</div>

            <!-- ACTIONS -->

            <div style="
                display:flex;
                justify-content:space-between;
                padding:12px;
                font-size:22px;
            ">

                <div>

                    ❤️

                    &nbsp;&nbsp;

                    💬

                    &nbsp;&nbsp;

                    ✈️

                </div>

                <div>

                    🔖

                </div>

            </div>

            <!-- CAPTION -->

            <div style="
                padding:10px 12px 16px 12px;
                font-size:14px;
                line-height:1.5;
            ">

                <strong>'
                    . e($username) .
                '</strong>

                '

                . nl2br(e($record->caption))

                . '

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
                        in_array($record->status, ['draft', 'scheduled', 'failed'])
                        && (auth()->user()?->hasPermissionTo('post.publish') ?? false)
                    )
                    ->action(function (Post $record) {
                        $platforms = $record->socialAccounts
                            ->pluck('platform')
                            ->map(fn ($p) => strtolower($p))
                            ->toArray();

                        $errors = [];

                        if (in_array('instagram', $platforms)) {
                            $result = self::publishInstagram($record);
                            if (!$result['success']) {
                                $errors[] = 'Instagram: ' . $result['message'];
                            }
                        }

                        if (in_array('facebook', $platforms)) {
                            $result = self::publishFacebook($record);
                            if (!$result['success']) {
                                $errors[] = 'Facebook: ' . $result['message'];
                            }
                        }

                        if (empty($errors)) {
                            $record->update([
                                'status'       => 'published',
                                'published_at' => now(),
                            ]);
                            // Fetch post URLs in background after platform processes the post
                            FetchPostUrlJob::dispatch($record->id)->delay(now()->addSeconds(30));
                            Notification::make()
                                ->title('Post berhasil dipublish')
                                ->success()
                                ->send();
                        } else {
                            $record->update(['status' => 'failed']);
                            Notification::make()
                                ->title('Publish gagal')
                                ->body(implode("\n", $errors))
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

    /*
    |--------------------------------------------------------------------------
    | EAGER LOAD
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('publishResults.socialAccount');
    }
}