<?php

namespace App\Filament\Resources\Messages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('socialAccount.id')
                    ->searchable(),
                TextColumn::make('platform_message_id')
                    ->searchable(),
                TextColumn::make('sender_id')
                    ->searchable(),
                TextColumn::make('sender_username')
                    ->searchable(),
                TextColumn::make('sender_avatar')
                    ->searchable(),
                TextColumn::make('platform')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                IconColumn::make('is_read')
                    ->boolean(),
                TextColumn::make('sent_at')
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
