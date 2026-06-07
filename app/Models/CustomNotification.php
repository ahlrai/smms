<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',

        'platform',
        'post_title',
        'status',
        'post_url',

        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'schedule' => '⏰',
            'warning'  => '⚠️',
            'message'  => '💬',
            'comment'  => '💭',
            'success'  => '✅',
            'error'    => '❌',
            'danger'   => '❌',
            default    => 'ℹ️',
        };
    }

    public static function notifyAdmins(
        string $title,
        string $message,
        string $type = 'info',
        ?string $platform = null,
        ?string $postTitle = null,
        ?string $status = null,
        ?string $postUrl = null,
        ?string $actionUrl = null
    ): void {

        $admins = User::role('admin')
            ->where('status', 'active')
            ->get();

        foreach ($admins as $admin) {

            static::create([
                'user_id'     => $admin->id,
                'title'       => $title,
                'message'     => $message,
                'type'        => $type,

                'platform'    => $platform,
                'post_title'  => $postTitle,
                'status'      => $status,
                'post_url'    => $postUrl,

                'action_url'  => $actionUrl,
            ]);
        }
    }

    public static function notifyUser(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',

        ?string $platform = null,
        ?string $postTitle = null,
        ?string $status = null,
        ?string $postUrl = null,

        ?string $actionUrl = null
    ): void {

        static::create([
            'user_id'     => $userId,
            'title'       => $title,
            'message'     => $message,
            'type'        => $type,

            'platform'    => $platform,
            'post_title'  => $postTitle,
            'status'      => $status,
            'post_url'    => $postUrl,

            'action_url'  => $actionUrl,
        ]);
    }
}