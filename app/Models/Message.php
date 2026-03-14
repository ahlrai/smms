<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'platform_message_id',
        'sender_id',
        'sender_username',
        'sender_avatar',
        'platform',
        'message',
        'status',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'sent_at'  => 'datetime',
        'is_read'  => 'boolean',
    ];

    // ── RELATIONS ──────────────────────────────────────────────

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(MessageReply::class)->orderBy('created_at');
    }

    // ── SCOPES ─────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeFollowUp($query)
    {
        return $query->where('status', 'follow-up');
    }

    public function scopeFacebook($query)
    {
        return $query->where('platform', 'facebook');
    }

    public function scopeInstagram($query)
    {
        return $query->where('platform', 'instagram');
    }

    // ── HELPERS ────────────────────────────────────────────────

    public function isNew(): bool      { return $this->status === 'new'; }
    public function isResolved(): bool { return $this->status === 'resolved'; }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function markAsFollowUp(): void
    {
        $this->update(['status' => 'follow-up']);
    }

    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }

    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    public function getLatestReply(): ?MessageReply
    {
        return $this->replies()->latest()->first();
    }
}