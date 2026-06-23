<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. DEFINE ALL PERMISSIONS ───────────────────────────────────────
        $allPermissions = [
            // Posts
            'post.create',
            'post.edit',
            'post.delete',
            'post.publish',
            'post.schedule',
            // Comments
            'comment.view',
            'comment.reply',
            // Messages
            'message.view',
            'message.reply',
            // Analytics
            'analytics.view',
            'analytics.full',
            // Social accounts
            'social.manage',
            // User management
            'users.manage',
            'roles.manage',
        ];

        // Remove deprecated permissions that no longer belong to the canonical set
        Permission::whereNotIn('name', $allPermissions)->delete();

        // Create new permissions idempotently
        foreach ($allPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ── 2. ROLES & THEIR PERMISSIONS ────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'post.create',
            'post.schedule',
            'comment.view',
            'comment.reply',
            'message.view',
            'message.reply',
            'analytics.view',
        ]);

        // ── 3. ASSIGN ROLES TO EXISTING USERS ───────────────────────────────
        $assignments = [
            'admin@example.com'           => 'admin',
            'admin@gmail.com'             => 'admin',
            'raihanahanasahlla@gmail.com' => 'admin',
            'staff1@example.com'          => 'staff',
            'staff2@example.com'          => 'staff',
        ];

        $emailCol = 'email';
        foreach ($assignments as $emailAddress => $roleName) {
            $user = User::firstWhere($emailCol, $emailAddress);
            $user?->syncRoles([$roleName]);
        }

        // ── 4. CLEAR CACHE ──────────────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
