<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_social_accounts', 'manage_social_accounts',
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts',
            'publish_posts', 'schedule_posts',
            'view_messages', 'reply_messages',
            'view_comments', 'reply_comments',
            'view_analytics', 'view_full_analytics',
            'manage_users', 'manage_roles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Admin — semua permission
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Staff — permission terbatas
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->givePermissionTo([
            'view_social_accounts',
            'view_posts', 'create_posts', 'schedule_posts',
            'view_messages', 'reply_messages',
            'view_comments', 'reply_comments',
            'view_analytics',
        ]);
    }
}