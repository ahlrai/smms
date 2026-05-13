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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
                ->label('Upload Media (Gambar/Video)')
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->image()
                ->disk('public')
                ->directory('post-media')
                ->visibility('public')
                ->preserveFilenames()
                ->panelLayout('grid')
                ->imagePreviewHeight('150')
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

                EditAction::make()
                    ->visible(fn(Post $record) => $record->status !== 'published'),

                /*
                |--------------------------------------------------------------------------
                | PREVIEW
                |--------------------------------------------------------------------------
                */

                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('7xl')
                    ->modalHeading('Preview Postingan')

                    ->modalContent(function (Post $record) {

                    $accounts = $record->socialAccounts;

                    /*
                    |--------------------------------------------------------------------------
                    | AMBIL MEDIA DARI KOLOM media
                    |--------------------------------------------------------------------------
                    */

                    $formattedMedia = [];

                    $mediaFiles = $record->media ?? [];

                    foreach ($mediaFiles as $media) {

                        if (!$media) {
                            continue;
                        }

                        $media = str_replace('\\', '/', $media);

                        $media = ltrim($media, '/');

                        $media = str_replace('storage/', '', $media);

                        $formattedMedia[] = asset('storage/' . $media);
                    }

                    $totalMedia = count($formattedMedia);

                    $html = '
                    <div style="
                        display:flex;
                        gap:30px;
                        flex-wrap:wrap;
                        align-items:flex-start;
                    ">
                    ';

                    foreach ($accounts as $account) {

                        /*
                        |--------------------------------------------------------------------------
                        | INSTAGRAM
                        |--------------------------------------------------------------------------
                        */

                        if (strtolower($account->platform) == 'instagram') {

                            $postDate = $record->published_at
                                ? \Carbon\Carbon::parse($record->published_at)
                                    ->translatedFormat('j F')
                                : now()->translatedFormat('j F');

                            $html .= '
                            <div style="
                                width:380px;
                                background:white;
                                border-radius:12px;
                                overflow:hidden;
                                border:1px solid #dbdbdb;
                                font-family:Arial,sans-serif;
                            ">

                                <div style="
                                    display:flex;
                                    justify-content:space-between;
                                    align-items:center;
                                    padding:12px 14px;
                                ">

                                    <div style="
                                        display:flex;
                                        align-items:center;
                                        gap:10px;
                                    ">

                                        <img
                                            src="https://ui-avatars.com/api/?name=' . $account->username . '&background=random"
                                            style="
                                                width:34px;
                                                height:34px;
                                                border-radius:50%;
                                                object-fit:cover;
                                            "
                                        >

                                        <div style="
                                            font-size:14px;
                                            font-weight:600;
                                            color:#262626;
                                        ">
                                            ' . $account->username . '
                                        </div>

                                    </div>

                                    <div style="font-size:20px;">
                                        •••
                                    </div>

                                </div>

                                <div style="
                                    display:flex;
                                    overflow-x:auto;
                                ">
                            ';

                            foreach ($formattedMedia as $imageUrl) {

                                $html .= '
                                <img
                                    src="' . $imageUrl . '"
                                    style="
                                        width:100%;
                                        aspect-ratio:4/5;
                                        object-fit:cover;
                                        flex-shrink:0;
                                        display:block;
                                    "
                                >
                                ';
                            }

                            $html .= '
                                </div>

                                <div style="
                                    display:flex;
                                    justify-content:space-between;
                                    align-items:center;
                                    padding:10px 14px;
                                ">

                                    <div style="
                                        display:flex;
                                        gap:18px;
                                        font-size:24px;
                                    ">
                                        <span>🤍</span>
                                        <span>💬</span>
                                        <span>📤</span>
                                    </div>

                                    <span style="font-size:24px;">
                                        🔖
                                    </span>

                                </div>

                                <div style="
                                    padding:0 14px;
                                    font-size:15px;
                                    font-weight:600;
                                    color:#262626;
                                ">
                                    Disukai oleh pengguna lain
                                </div>

                                <div style="
                                    padding:8px 14px 0;
                                    font-size:15px;
                                    line-height:1.5;
                                    color:#262626;
                                ">

                                    <strong>' . $account->username . '</strong>

                                    ' . $record->caption . '

                                </div>

                                <div style="
                                    padding:10px 14px 16px;
                                    font-size:12px;
                                    color:#8e8e8e;
                                    text-transform:uppercase;
                                ">
                                    ' . $postDate . '
                                </div>

                            </div>
                            ';
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | FACEBOOK
                        |--------------------------------------------------------------------------
                        */

                        if (strtolower($account->platform) == 'facebook') {

                            $postTime = $record->published_at
                                ? \Carbon\Carbon::parse($record->published_at)
                                    ->format('d M Y H:i')
                                : now()->format('d M Y H:i');

                            $html .= '
                            <div style="
                                width:420px;
                                background:#f0f2f5;
                                padding:20px;
                                border-radius:18px;
                                font-family:Arial,sans-serif;
                            ">

                                <div style="
                                    background:white;
                                    border-radius:14px;
                                    overflow:hidden;
                                    box-shadow:0 1px 3px rgba(0,0,0,.12);
                                ">

                                    <div style="
                                        display:flex;
                                        gap:12px;
                                        align-items:center;
                                        padding:14px;
                                    ">

                                        <img
                                            src="https://ui-avatars.com/api/?name=' . $account->username . '&background=random"
                                            style="
                                                width:48px;
                                                height:48px;
                                                border-radius:50%;
                                                object-fit:cover;
                                            "
                                        >

                                        <div>

                                            <div style="
                                                font-size:18px;
                                                font-weight:700;
                                                color:#050505;
                                            ">
                                                ' . $account->username . '
                                            </div>

                                            <div style="
                                                font-size:14px;
                                                color:#65676b;
                                                margin-top:2px;
                                            ">
                                                ' . $postTime . '
                                            </div>

                                        </div>

                                    </div>

                                    <div style="
                                        padding:0 14px 14px;
                                        font-size:17px;
                                        line-height:1.5;
                                        color:#050505;
                                    ">
                                        ' . $record->caption . '
                                    </div>

                                    <div style="
                                        display:flex;
                                        overflow-x:auto;
                                    ">
                            ';

                            foreach ($formattedMedia as $imageUrl) {

                                $html .= '
                                <img
                                    src="' . $imageUrl . '"
                                    style="
                                        width:100%;
                                        max-height:700px;
                                        object-fit:cover;
                                        flex-shrink:0;
                                        display:block;
                                    "
                                >
                                ';
                            }

                            $html .= '
                                    </div>

                                    <div style="
                                        border-top:1px solid #ddd;
                                        display:flex;
                                        justify-content:space-around;
                                        padding:14px 10px;
                                        font-size:15px;
                                        color:#65676b;
                                        font-weight:600;
                                    ">

                                        <div>👍 Suka</div>

                                        <div>💬 Komentar</div>

                                        <div>↗ Bagikan</div>

                                    </div>

                                </div>

                            </div>
                            ';
                        }
                    }

                    $html .= '</div>';

                    return new HtmlString($html);
                }),

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