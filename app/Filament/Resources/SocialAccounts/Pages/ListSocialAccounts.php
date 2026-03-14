<?php

namespace App\Filament\Resources\SocialAccounts\Pages;

use App\Filament\Resources\SocialAccounts\SocialAccountResource;
use App\Models\Metric;
use App\Models\Post;
use App\Models\SocialAccount;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;

class ListSocialAccounts extends ListRecords
{
    protected static string $resource = SocialAccountResource::class;
    protected string $view = 'filament.resources.social-accounts.list';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('connect_facebook')
                    ->label('Facebook Page')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->url(route('auth.facebook.redirect')),

                Action::make('connect_instagram')
                    ->label('Instagram Business')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->url(route('auth.instagram.redirect')),
            ])
            ->label('Hubungkan Akun')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->button(),
        ];
    }

    public function getAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return SocialAccount::with('creator')->get();
    }

    public function getAccountStats(SocialAccount $account): array
    {
        $postCount = Post::where('social_account_id', $account->id)
            ->where('status', 'published')
            ->count();

        $totalReach = Metric::where('social_account_id', $account->id)
            ->whereNull('post_id')
            ->where('recorded_date', '>=', now()->subDays(30)->toDateString())
            ->sum('reach');

        $totalLikes = Metric::where('social_account_id', $account->id)
            ->where('recorded_date', '>=', now()->subDays(30)->toDateString())
            ->sum('likes');

        return [
            'posts'  => $postCount,
            'reach'  => $totalReach,
            'likes'  => $totalLikes,
        ];
    }

    public function deleteAccount(int $id): void
    {
        SocialAccount::findOrFail($id)->delete();
        $this->dispatch('$refresh');
    }
}
