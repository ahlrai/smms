<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env(
            'AWS_DEFAULT_REGION',
            'us-east-1'
        ),
    ],

    'slack' => [
        'notifications' => [

            'bot_user_oauth_token' =>
                env(
                    'SLACK_BOT_USER_OAUTH_TOKEN'
                ),

            'channel' =>
                env(
                    'SLACK_BOT_USER_DEFAULT_CHANNEL'
                ),
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | FACEBOOK
    |--------------------------------------------------------------------------
    */

    'facebook' => [

        'app_id' =>

            env(
                'FACEBOOK_APP_ID'
            ),

        'app_secret' =>

            env(
                'FACEBOOK_APP_SECRET'
            ),

        'callback_url' =>

            env(
                'FACEBOOK_CALLBACK_URL'
            ),

        'graph_version' =>

            env(
                'FACEBOOK_GRAPH_VERSION',
                'v22.0'
            ),

    ],


    /*
    |--------------------------------------------------------------------------
    | INSTAGRAM
    |--------------------------------------------------------------------------
    */

    'instagram' => [

        'callback_url' =>

            env(
                'INSTAGRAM_CALLBACK_URL'
            ),

    ],

];