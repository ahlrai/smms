<?php

namespace App\Filament\Resources\SocialAccounts;

use App\Filament\Resources\SocialAccounts\Pages;
use App\Models\SocialAccount;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static string|\UnitEnum|null $navigationGroup = 'Social Media';

    protected static ?string $navigationLabel = 'Akun Sosial';

    protected static ?int $navigationSort = 1;

    // Social accounts are visible only to users who manage them.
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermissionTo('social.manage') ?? false;
    }

    // Accounts are connected via OAuth; the Filament create form is disabled.
    public static function canCreate(): bool
    {
        return false;
    }

    // Only users with social.manage (admins) may delete a connected account.
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasPermissionTo('social.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Select::make('platform')
                ->label('Platform')
                ->options([
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                ])
                ->disabled()
                ->dehydrated(false),

            TextInput::make('username')
                ->label('Username')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('display_name')
                ->label('Display Name')
                ->placeholder('Nama yang ditampilkan')
                ->maxLength(255),

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
                        'facebook' => 'primary',
                        'instagram' => 'pink',
                        default => 'gray',
                    }),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),

                TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable()
                    ->placeholder('-'),

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
            'index' => Pages\ListSocialAccounts::route('/'),
            'create' => Pages\CreateSocialAccount::route('/create'),
            'edit' => Pages\EditSocialAccount::route('/{record}/edit'),
        ];
    }
}