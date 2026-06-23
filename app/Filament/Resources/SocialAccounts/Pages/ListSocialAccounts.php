<?php

namespace App\Filament\Resources\SocialAccounts\Pages;

use App\Filament\Resources\SocialAccounts\SocialAccountResource;
use App\Models\SocialAccount;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSocialAccounts extends ListRecords
{
    protected static string $resource =
        SocialAccountResource::class;

    protected string $view =
        'filament.resources.social-accounts.list';

    protected function getHeaderActions(): array
    {
        return [

            Action::make('connect_account')

                ->label('Hubungkan Akun')

                ->icon('heroicon-o-link')

                ->color('primary')

                ->url(
                    route('auth.instagram.redirect')
                ),

        ];
    }

    public function getAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return SocialAccount::with('creator')->get();
    }

    public function deleteAccount(int $id): void
    {
        SocialAccount::findOrFail($id)->delete();

        $this->dispatch('$refresh');
    }
}