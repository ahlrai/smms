<?php

namespace App\Filament\Resources\SocialAccounts;

use App\Filament\Resources\SocialAccounts\Pages;
use App\Models\SocialAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-share';
    protected static string|\UnitEnum|null  $navigationGroup = 'Social Media';
    protected static ?string $navigationLabel = 'Akun Sosial';
    protected static ?int    $navigationSort  = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('platform')
                ->options(['facebook' => 'Facebook', 'instagram' => 'Instagram'])
                ->required(),

            TextInput::make('username')
                ->label('Username / Page Name')
                ->required(),

            TextInput::make('account_id')
                ->label('Account ID / Page ID')
                ->required(),

            TextInput::make('access_token')
                ->label('Access Token')
                ->password()
                ->required(),

            TextInput::make('refresh_token')
                ->label('Refresh Token')
                ->password(),

            DateTimePicker::make('token_expired_at')
                ->label('Token Expired At'),

            Select::make('created_by')
                ->label('Dibuat Oleh')
                ->relationship('creator', 'name')
                ->default(auth()->id())
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'facebook'  => 'primary',
                        'instagram' => 'pink',
                        default     => 'gray',
                    }),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),

                TextColumn::make('account_id')
                    ->label('Account ID')
                    ->copyable(),

                TextColumn::make('token_expired_at')
                    ->label('Token Expired')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record?->isTokenExpiringSoon() ? 'danger' : 'success'),

                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSocialAccounts::route('/'),
            'create' => Pages\CreateSocialAccount::route('/create'),
            'edit'   => Pages\EditSocialAccount::route('/{record}/edit'),
        ];
    }
}