<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostMedia extends Model
{
    use HasFactory;

    protected $table = 'post_media';

    protected $fillable = [
        'post_id',
        'file_path',
        'file_name',
        'media_type',
        'file_size',
        'mime_type',
        'platform_media_id',
        'sort_order',
    ];

    // ── RELATIONS ──────────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // ── HELPERS ────────────────────────────────────────────────

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    // Ambil URL publik file
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    // Ukuran file dalam format yang mudah dibaca (KB, MB)
    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) return '-';

        $kb = $this->file_size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';

        return round($kb / 1024, 1) . ' MB';
    }

    // ── SCOPES ─────────────────────────────────────────────────

    public function scopeImages($query)
    {
        return $query->where('media_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', 'video');
    }
}