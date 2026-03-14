<?php

namespace App\Filament\Resources\Messages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('social_account_id')
                    ->relationship('socialAccount', 'id')
                    ->required(),
                TextInput::make('platform_message_id')
                    ->required(),
                TextInput::make('sender_id'),
                TextInput::make('sender_username')
                    ->required(),
                TextInput::make('sender_avatar'),
                TextInput::make('platform')
                    ->required(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                Toggle::make('is_read')
                    ->required(),
                DateTimePicker::make('sent_at')
                    ->required(),
            ]);
    }
}
