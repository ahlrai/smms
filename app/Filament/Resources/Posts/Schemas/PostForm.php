<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('social_account_id')
                    ->relationship('socialAccount', 'id')
                    ->required(),
                TextInput::make('platform')
                    ->required(),
                Textarea::make('caption')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('platform_post_id'),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('published_at'),
                Textarea::make('fail_reason')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
