<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'username',
        'account_id',
        'access_token',
        'refresh_token',
        'token_expired_at',
        'created_by',
    ];

    protected $casts = [
        'token_expired_at' => 'datetime',
    ];

    // ── TOKEN ENKRIPSI ─────────────────────────────────────────
    // Token otomatis dienkripsi saat disimpan & didekripsi saat dibaca

    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAccessTokenAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // kembalikan apa adanya jika gagal decrypt
        }
    }

    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    public function getRefreshTokenAttribute(?string $value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    // ── RELATIONS ──────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserSocialPermission::class);
    }

    // ── HELPERS ────────────────────────────────────────────────

    public function isTokenExpired(): bool
    {
        return $this->token_expired_at && $this->token_expired_at->isPast();
    }

    public function isTokenExpiringSoon(int $days = 7): bool
    {
        return $this->token_expired_at
            && $this->token_expired_at->isBefore(now()->addDays($days));
    }

    public function isFacebook(): bool
    {
        return $this->platform === 'facebook';
    }

    public function isInstagram(): bool
    {
        return $this->platform === 'instagram';
    }

    public function getPlatformLabelAttribute(): string
    {
        return ucfirst($this->platform);
    }

    // ── SCOPES ─────────────────────────────────────────────────

    public function scopeFacebook($query)
    {
        return $query->where('platform', 'facebook');
    }

    public function scopeInstagram($query)
    {
        return $query->where('platform', 'instagram');
    }

    public function scopeTokenExpiringSoon($query, int $days = 7)
    {
        return $query->where('token_expired_at', '<=', now()->addDays($days))
                     ->where('token_expired_at', '>', now());
    }
}