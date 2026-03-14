<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model                          = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup  = 'User Management';
    protected static ?string $navigationLabel                = 'Manajemen User';
    protected static ?int    $navigationSort                 = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nama Lengkap')
                ->required(),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->unique(ignoreRecord: true)
                ->required(),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context) => $context === 'create'),

            Select::make('roles')
                ->label('Role')
                ->relationship('roles', 'name')
                ->preload()
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'active'   => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'success',
                        'staff' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->native(false)
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),

                SelectFilter::make('roles')->native(false)
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}