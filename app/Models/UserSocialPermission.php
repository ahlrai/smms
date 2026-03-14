<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id',
        'can_view',
        'can_create_post',
        'can_schedule_post',
        'can_publish_post',
        'can_reply_message',
        'can_reply_comment',
        'can_view_analytics',
    ];

    protected $casts = [
        'can_view'           => 'boolean',
        'can_create_post'    => 'boolean',
        'can_schedule_post'  => 'boolean',
        'can_publish_post'   => 'boolean',
        'can_reply_message'  => 'boolean',
        'can_reply_comment'  => 'boolean',
        'can_view_analytics' => 'boolean',
    ];

    // ── RELATIONS ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    // ── HELPERS ────────────────────────────────────────────────

    // Cek apakah user punya permission tertentu untuk akun sosial ini
    public static function userCan(
        int $userId,
        int $socialAccountId,
        string $permission
    ): bool {
        $record = static::where('user_id', $userId)
                        ->where('social_account_id', $socialAccountId)
                        ->first();

        if (!$record) return false;

        return (bool) $record->$permission;
    }

    // Buat default permission untuk staff baru
    public static function createDefault(int $userId, int $socialAccountId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'social_account_id' => $socialAccountId],
            [
                'can_view'           => true,
                'can_create_post'    => true,
                'can_schedule_post'  => true,
                'can_publish_post'   => false,
                'can_reply_message'  => true,
                'can_reply_comment'  => true,
                'can_view_analytics' => true,
            ]
        );
    }
}