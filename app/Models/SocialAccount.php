<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use HasFactory;

    protected $table = 'social_accounts';

    protected $fillable = [

        'platform',
        'username',
        'account_id',

        'access_token',
        'refresh_token',

        'token_expired_at',

        'created_by'

    ];


    protected $casts = [

        'token_expired_at'
            => 'datetime'

    ];


    /*
    |--------------------------------------------------------------------------
    | ACCESS TOKEN
    |--------------------------------------------------------------------------
    */

    public function setAccessTokenAttribute($value)
    {
        if (!$value) {

            $this->attributes['access_token']
                = null;

            return;
        }


        $this->attributes['access_token']
            = Crypt::encryptString($value);
    }


    public function getAccessTokenAttribute($value)
    {
        if (!$value) {

            return null;
        }


        try {

            return Crypt::decryptString(
                $value
            );

        }

        catch (\Exception $e) {

            return $value;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN
    |--------------------------------------------------------------------------
    */

    public function setRefreshTokenAttribute($value)
    {
        if (!$value) {

            $this->attributes['refresh_token']
                = null;

            return;
        }


        $this->attributes['refresh_token']
            = Crypt::encryptString($value);
    }


    public function getRefreshTokenAttribute($value)
    {
        if (!$value) {

            return null;
        }


        try {

            return Crypt::decryptString(
                $value
            );

        }

        catch (\Exception $e) {

            return $value;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function posts(): HasMany
    {
        return $this->hasMany(
            Post::class
        );
    }


    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class
        );
    }



    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isTokenExpired(): bool
    {
        return

            $this->token_expired_at

            &&

            $this->token_expired_at
                ->isPast();
    }


    public function isInstagram(): bool
    {
        return

            $this->platform

            ===

            'instagram';
    }


    public function isFacebook(): bool
    {
        return

            $this->platform

            ===

            'facebook';
    }
}