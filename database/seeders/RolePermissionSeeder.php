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
            // Panel entry — every user who may log in must have this
            'panel.access',
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
            // Notifications
            'notifications.view',
        ];

        // Remove deprecated permissions that no longer belong to the canonical set
        Permission::whereNotIn('name', $allPermissions)->delete();

        // Create new permissions idempotently
        foreach ($allPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ── 2. ROLES & THEIR PERMISSIONS ────────────────────────────────────
        // Roles are permission bundles only. Authorization checks always use
        // hasPermissionTo(), never hasRole(). Assigning a role grants its
        // bundled permissions — nothing more.

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // staff: full social-media operator — posts, inbox, comments, analytics
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'panel.access',
            'post.create',
            'post.edit',
            'post.schedule',
            'post.publish',
            'analytics.view',
            'notifications.view',
        ]);

        // content_creator: only creates and schedules posts — nothing else visible
        $contentCreator = Role::firstOrCreate(['name' => 'content_creator', 'guard_name' => 'web']);
        $contentCreator->syncPermissions([
            'panel.access',
            'post.create',
            'post.schedule',
            'post.publish',
            'analytics.view',
            'notifications.view',
        ]);

        // social_manager: manages connected social accounts only
        $socialManager = Role::firstOrCreate(['name' => 'social_manager', 'guard_name' => 'web']);
        $socialManager->syncPermissions([
            'panel.access',
            'analytics.view',
            'notifications.view',
            'social.manage',
        ]);

        // ── 3. SEED INITIAL ADMIN ───────────────────────────────────────────
        // Assigns admin role to the email in ADMIN_EMAIL env var (dev bootstrap only).
        // In production, assign roles through the Users management page instead.
        $adminEmail = env('ADMIN_EMAIL');
        if ($adminEmail) {
            $user = User::firstWhere('email', $adminEmail);
            $user?->syncRoles(['admin']);
        }

        // ── 4. CLEAR CACHE ──────────────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
