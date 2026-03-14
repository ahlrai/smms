<?php

namespace App\Filament\Resources\Comments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post.id')
                    ->searchable(),
                TextColumn::make('socialAccount.id')
                    ->searchable(),
                TextColumn::make('platform_comment_id')
                    ->searchable(),
                TextColumn::make('commenter_id')
                    ->searchable(),
                TextColumn::make('commenter_username')
                    ->searchable(),
                TextColumn::make('commenter_avatar')
                    ->searchable(),
                TextColumn::make('platform')
                    ->searchable(),
                TextColumn::make('like_count')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_replied')
                    ->boolean(),
                IconColumn::make('is_hidden')
                    ->boolean(),
                TextColumn::make('parent_comment_id')
                    ->searchable(),
                TextColumn::make('commented_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
