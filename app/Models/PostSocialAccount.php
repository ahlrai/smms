<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PostSocialAccount extends Pivot
{
    protected $table = 'post_social_accounts';

    public $incrementing = true;

    protected $fillable = [
        'post_id',
        'social_account_id',
        'platform_post_id',
        'post_url',
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
