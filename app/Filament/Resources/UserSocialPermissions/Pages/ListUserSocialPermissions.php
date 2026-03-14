<?php

namespace App\Filament\Resources\UserSocialPermissions\Pages;

use App\Filament\Resources\UserSocialPermissions\UserSocialPermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserSocialPermissions extends ListRecords
{
    protected static string $resource = UserSocialPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
