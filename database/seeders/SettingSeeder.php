<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set(
            'facebook_app_id',
            env('FACEBOOK_APP_ID')
        );

        Setting::set(
            'facebook_app_secret',
            env('FACEBOOK_APP_SECRET')
        );

        Setting::set(
            'facebook_graph_version',
            env('FACEBOOK_GRAPH_VERSION', 'v22.0')
        );

        Setting::set(
            'facebook_callback_url',
            env('FACEBOOK_CALLBACK_URL')
        );

        Setting::set(
            'instagram_callback_url',
            env('INSTAGRAM_CALLBACK_URL')
        );

        Setting::set(
            'meta_verify_token',
            env('META_VERIFY_TOKEN')
        );
    }
}