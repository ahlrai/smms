<?php

namespace App\Filament\Resources\UserSocialPermissions\Pages;

use App\Filament\Resources\UserSocialPermissions\UserSocialPermissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserSocialPermission extends EditRecord
{
    protected static string $resource = UserSocialPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
