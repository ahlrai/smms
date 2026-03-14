<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
    ];

    // Wajib ada — menentukan siapa yang bisa akses Filament panel
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active';
    }

    // ── RELATIONS ──────────────────────────────────────────────

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'created_by');
    }

    public function messageReplies(): HasMany
    {
        return $this->hasMany(MessageReply::class, 'replied_by');
    }

    public function commentReplies(): HasMany
    {
        return $this->hasMany(CommentReply::class, 'replied_by');
    }

    public function customNotifications(): HasMany
    {
        return $this->hasMany(CustomNotification::class);
    }

    public function socialPermissions(): HasMany
    {
        return $this->hasMany(UserSocialPermission::class);
    }

    // ── HELPERS ────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function unreadNotificationsCount(): int
    {
        return $this->customNotifications()->where('is_read', false)->count();
    }
}
