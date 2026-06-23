<?php

namespace Database\Seeders;

use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserSocialPermission;
use Illuminate\Database\Seeder;

class UserSocialPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (UserSocialPermission::exists()) {
            $this->command->info('UserSocialPermission records already exist — skipping.');
            return;
        }

        // Staff-role defaults per account
        $staffDefaults = [
            'can_view'           => true,
            'can_create_post'    => true,
            'can_schedule_post'  => true,
            'can_publish_post'   => false,
            'can_reply_comment'  => true,
            'can_reply_message'  => true,
            'can_view_analytics' => true,
        ];

        // email → per-account overrides (null = use staffDefaults)
        $assignments = [
            'staff1@example.com' => null,
            'staff2@example.com' => [
                'can_schedule_post'  => false,
                'can_publish_post'   => false,
                'can_reply_message'  => false,
            ],
        ];

        $accounts = SocialAccount::all();

        if ($accounts->isEmpty()) {
            $this->command->warn('No social accounts found — nothing to seed.');
            return;
        }

        $emailCol = 'email';
        $created  = 0;

        foreach ($assignments as $email => $overrides) {
            $user = User::firstWhere($emailCol, $email);

            if (! $user) {
                $this->command->warn("User [{$email}] not found — skipped.");
                continue;
            }

            $permissions = array_merge(
                $staffDefaults,
                $overrides ?? []
            );

            foreach ($accounts as $account) {
                UserSocialPermission::firstOrCreate(
                    [
                        'user_id'           => $user->id,
                        'social_account_id' => $account->id,
                    ],
                    $permissions
                );
                $created++;
            }
        }

        $this->command->info("Seeded {$created} UserSocialPermission record(s).");
    }
}
