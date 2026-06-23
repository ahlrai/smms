<?php

namespace App\Filament\Resources\UserSocialPermissions\Pages;

use App\Filament\Resources\UserSocialPermissions\UserSocialPermissionResource;
use App\Models\UserSocialPermission;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserSocialPermission extends CreateRecord
{
    protected static string $resource = UserSocialPermissionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return UserSocialPermission::updateOrCreate(
            [
                'user_id'           => $data['user_id'],
                'social_account_id' => $data['social_account_id'],
            ],
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
