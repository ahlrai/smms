<?php

namespace App\Filament\Resources\UserSocialPermissions\Pages;

use App\Filament\Resources\UserSocialPermissions\UserSocialPermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserSocialPermission extends CreateRecord
{
    protected static string $resource = UserSocialPermissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
