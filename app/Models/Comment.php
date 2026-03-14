<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'social_account_id',
        'platform_comment_id',
        'commenter_id',
        'commenter_username',
        'commenter_avatar',
        'platform',
        'content',
        'like_count',
        'is_replied',
        'is_hidden',
        'parent_comment_id',
        'commented_at',
    ];

    protected $casts = [
        'commented_at' => 'datetime',
        'is_replied'   => 'boolean',
        'is_hidden'    => 'boolean',
    ];

    // ── RELATIONS ──────────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommentReply::class)->orderBy('created_at');
    }

    // ── SCOPES ─────────────────────────────────────────────────

    public function scopeUnreplied($query)
    {
        return $query->where('is_replied', false);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeFacebook($query)
    {
        return $query->where('platform', 'facebook');
    }

    public function scopeInstagram($query)
    {
        return $query->where('platform', 'instagram');
    }

    // Komentar level teratas (bukan reply)
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    // ── HELPERS ────────────────────────────────────────────────

    public function markAsReplied(): void
    {
        $this->update(['is_replied' => true]);
    }

    public function hide(): void
    {
        $this->update(['is_hidden' => true]);
    }

    public function show(): void
    {
        $this->update(['is_hidden' => false]);
    }

    public function isTopLevel(): bool
    {
        return is_null($this->parent_comment_id);
    }
}