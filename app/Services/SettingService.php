<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{

    public static function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    public static function facebookAppId(): string
    {
        return Setting::get(
            'facebook_app_id',
            ''
        );
    }

    public static function facebookAppSecret(): string
    {
        return Setting::get(
            'facebook_app_secret',
            ''
        );
    }

    public static function graphVersion(): string
    {
        return Setting::get(
            'facebook_graph_version',
            'v22.0'
        );
    }

    public static function facebookCallbackUrl(): string
    {
        return Setting::get(
            'facebook_callback_url',
            url('/auth/facebook/callback')
        );
    }

    public static function instagramCallbackUrl(): string
    {
        return Setting::get(
            'instagram_callback_url',
            url('/auth/instagram/callback')
        );
    }

    public static function verifyToken(): string
    {
        return Setting::get(
            'meta_verify_token',
            ''
        );
    }
}