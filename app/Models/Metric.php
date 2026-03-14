<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'post_id',
        'platform',
        'likes',
        'comments',
        'shares',
        'reach',
        'impressions',
        'saves',
        'clicks',
        'recorded_date',
    ];

    protected $casts = [
        'recorded_date' => 'date',
    ];

    // ── RELATIONS ──────────────────────────────────────────────

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // ── SCOPES ─────────────────────────────────────────────────

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('social_account_id', $accountId);
    }

    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    public function scopeLastDays($query, int $days = 7)
    {
        return $query->where('recorded_date', '>=', now()->subDays($days)->toDateString());
    }

    public function scopeLastMonth($query)
    {
        return $query->where('recorded_date', '>=', now()->subDays(30)->toDateString());
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

    // Hitung engagement rate (likes + comments + shares / reach * 100)
    public function getEngagementRateAttribute(): float
    {
        if ($this->reach === 0) return 0;
        return round(($this->likes + $this->comments + $this->shares) / $this->reach * 100, 2);
    }

    public function getTotalEngagementAttribute(): int
    {
        return $this->likes + $this->comments + $this->shares + $this->saves;
    }
}