<?php

namespace App\Filament\Resources\SocialAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SocialAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('platform')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('account_id')
                    ->required(),
                DateTimePicker::make('token_expired_at'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
