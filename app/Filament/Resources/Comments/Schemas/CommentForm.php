<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('post_id')
                    ->relationship('post', 'id')
                    ->required(),
                Select::make('social_account_id')
                    ->relationship('socialAccount', 'id')
                    ->required(),
                TextInput::make('platform_comment_id')
                    ->required(),
                TextInput::make('commenter_id'),
                TextInput::make('commenter_username')
                    ->required(),
                TextInput::make('commenter_avatar'),
                TextInput::make('platform')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('like_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_replied')
                    ->required(),
                Toggle::make('is_hidden')
                    ->required(),
                TextInput::make('parent_comment_id'),
                DateTimePicker::make('commented_at')
                    ->required(),
            ]);
    }
}
