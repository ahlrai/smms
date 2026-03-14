<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'reply',
        'replied_by',
        'platform_reply_id',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    // ── RELATIONS ──────────────────────────────────────────────

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    // ── HELPERS ────────────────────────────────────────────────

    public function markAsSent(string $platformReplyId): void
    {
        $this->update([
            'is_sent'           => true,
            'platform_reply_id' => $platformReplyId,
            'sent_at'           => now(),
        ]);

        // Otomatis tandai komentar induk sebagai sudah dibalas
        $this->comment->markAsReplied();
    }
}