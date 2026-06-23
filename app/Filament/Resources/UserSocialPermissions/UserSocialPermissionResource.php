<?php

namespace App\Filament\Resources\UserSocialPermissions;

use App\Filament\Resources\UserSocialPermissions\Pages;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserSocialPermission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserSocialPermissionResource extends Resource
{
    protected static ?string $model                           = UserSocialPermission::class;
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-key';
    protected static string|\UnitEnum|null $navigationGroup   = 'User Management';
    protected static ?string $navigationLabel                 = 'Izin Akun Sosial';
    protected static ?int    $navigationSort                  = 2;

    // Hanya Admin yang bisa akses
    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('roles.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('Staff')
                ->options(
                    User::role('staff')->where('status', 'active')->pluck('name', 'id')
                )
                ->required()
                ->searchable(),

            Select::make('social_account_id')
                ->label('Akun Sosial')
                ->options(
                    SocialAccount::all()->mapWithKeys(fn ($a) => [
                        $a->id => '[' . strtoupper($a->platform) . '] ' . $a->username,
                    ])
                )
                ->required()
                ->searchable(),

            Section::make(...['Izin Akses'])
                ->description('Atur apa yang boleh dilakukan staff ini pada akun sosial tersebut.')
                ->columns(2)
                ->schema([
                    Toggle::make('can_view')
                        ->label('Lihat Akun')
                        ->default(true)
                        ->helperText('Akun sosial muncul di daftar staff ini.'),

                    Toggle::make('can_create_post')
                        ->label('Buat Post')
                        ->default(true)
                        ->helperText('Boleh membuat draft post.'),

                    Toggle::make('can_schedule_post')
                        ->label('Jadwalkan Post')
                        ->default(true)
                        ->helperText('Boleh menjadwalkan post.'),

                    Toggle::make('can_publish_post')
                        ->label('Publish Langsung')
                        ->default(false)
                        ->helperText('Boleh mempublish post tanpa persetujuan admin.'),

                    Toggle::make('can_reply_comment')
                        ->label('Balas Komentar')
                        ->default(true)
                        ->helperText('Boleh membalas komentar.'),

                    Toggle::make('can_reply_message')
                        ->label('Balas Pesan')
                        ->default(true)
                        ->helperText('Boleh membalas pesan masuk.'),

                    Toggle::make('can_view_analytics')
                        ->label('Lihat Analytics')
                        ->default(true)
                        ->helperText('Boleh melihat data metrik.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('socialAccount.platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'facebook'  => 'primary',
                        'instagram' => 'pink',
                        default     => 'gray',
                    }),

                TextColumn::make('socialAccount.username')
                    ->label('Akun Sosial')
                    ->searchable(),

                IconColumn::make('can_view')
                    ->label('Lihat')
                    ->boolean(),

                IconColumn::make('can_create_post')
                    ->label('Buat Post')
                    ->boolean(),

                IconColumn::make('can_schedule_post')
                    ->label('Jadwalkan')
                    ->boolean(),

                IconColumn::make('can_publish_post')
                    ->label('Publish')
                    ->boolean(),

                IconColumn::make('can_reply_comment')
                    ->label('Komentar')
                    ->boolean(),

                IconColumn::make('can_reply_message')
                    ->label('Pesan')
                    ->boolean(),

                IconColumn::make('can_view_analytics')
                    ->label('Analytics')
                    ->boolean(),
            ])
            ->defaultSort('user_id')
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Staff')
                    ->relationship('user', 'name'),

                SelectFilter::make('social_account_id')
                    ->label('Akun Sosial')
                    ->relationship('socialAccount', 'username'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUserSocialPermissions::route('/'),
            'create' => Pages\CreateUserSocialPermission::route('/create'),
            'edit'   => Pages\EditUserSocialPermission::route('/{record}/edit'),
        ];
    }
}
