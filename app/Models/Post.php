<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'platform',
        'title',
        'caption',
        'media',
        'status',
        'post_url',
        'scheduled_at',
        'published_at',
        'fail_reason',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'media' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | AUTO CREATED BY
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($post) {

            if (!$post->created_by && auth()->check()) {
                $post->created_by = auth()->id();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | GANTI NAMA RELATION
    |--------------------------------------------------------------------------
    */

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function socialAccounts()
    {
        return $this->belongsToMany(
            SocialAccount::class,
            'post_social_accounts',
            'post_id',
            'social_account_id'
        )
        ->using(PostSocialAccount::class)
        ->withPivot([
            'platform_post_id',
            'post_url',
        ]);
    }

    // Direct hasMany on the pivot rows — query publish results without going through SocialAccount
    public function publishResults()
    {
        return $this->hasMany(PostSocialAccount::class, 'post_id');
    }

    public function postMedia()
    {
        return $this->hasMany(PostMedia::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeUpcoming($query, int $minutes = 30)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addMinutes($minutes));
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeFacebook($query)
    {
        return $query->where('platform', 'facebook');
    }

    public function scopeInstagram($query)
    {
        return $query->where('platform', 'instagram');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR
    |--------------------------------------------------------------------------
    */

    protected function captionPreview(): Attribute
    {
        return Attribute::make(
            get: fn() =>
            strlen($this->caption) > 50
                ? substr($this->caption, 0, 50) . '...'
                : $this->caption
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function hasMedia(): bool
    {
        return !empty($this->media);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'published' => 'success',
            'scheduled' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    public function markAsPublished(string $platformPostId): void
    {
        $this->update([
            'status' => 'published',
            'platform_post_id' => $platformPostId,
            'published_at' => now(),
            'fail_reason' => null,
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'fail_reason' => $reason,
        ]);
    }
}