<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    private string $baseUrl;
    private string $version;


    public function __construct()
    {
        $this->version =
            config(
                'services.facebook.graph_version',
                'v22.0'
            );

        $this->baseUrl =
            'https://graph.facebook.com/'
            .
            $this->version;
    }



    /*
    |--------------------------------------------------------------------------
    | OAUTH LOGIN URL
    |--------------------------------------------------------------------------
    */

    public function getLoginUrl(): string
{
    $state = bin2hex(random_bytes(16));

    session([
        'fb_oauth_state' => $state
    ]);


    $scopes = [

        // Facebook Pages
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_metadata',
        'pages_manage_posts',

        // Instagram
        'instagram_basic',
        'instagram_content_publish',
        'instagram_manage_comments',
        'instagram_manage_messages',

        // Optional
        'business_management',

        // Default
        'public_profile'
    ];


    $params = [

        'client_id' =>
            config('services.facebook.app_id'),

        'redirect_uri' =>
            config('services.instagram.callback_url'),

        'scope' =>
            implode(',', $scopes),

        'response_type' =>
            'code',

        'state' =>
            $state,

        /*
        paksa Meta minta izin ulang
        */
        'auth_type' =>
            'reauthorize',

        /*
        nonce supaya Meta tidak cache login lama
        */
        'auth_nonce' =>
            time()
    ];


    $url =
        'https://www.facebook.com/v22.0/dialog/oauth?'
        .
        http_build_query(
            $params
        );


    Log::info(
        'LOGIN URL',
        [
            'url' => $url
        ]
    );


    return $url;
}



    /*
    |--------------------------------------------------------------------------
    | SHORT TOKEN
    |--------------------------------------------------------------------------
    */

    public function getShortLivedToken(
        string $code,
        bool $instagram = false
    ): array {

        $redirectUri =
            $instagram

            ?

            config(
                'services.instagram.callback_url'
            )

            :

            config(
                'services.facebook.callback_url'
            );


        $response =
            Http::get(

                'https://graph.facebook.com/oauth/access_token',

                [

                    'client_id' =>
                        config(
                            'services.facebook.app_id'
                        ),

                    'client_secret' =>
                        config(
                            'services.facebook.app_secret'
                        ),

                    'redirect_uri' =>
                        $redirectUri,

                    'code' =>
                        $code

                ]

            );


        return
            $response->json();
    }




    /*
    |--------------------------------------------------------------------------
    | LONG TOKEN
    |--------------------------------------------------------------------------
    */

    public function getLongLivedToken(
        string $token
    ): array {

        $response =
            Http::get(

                $this->baseUrl
                .
                '/oauth/access_token',

                [

                    'grant_type' =>
                        'fb_exchange_token',

                    'client_id' =>
                        config(
                            'services.facebook.app_id'
                        ),

                    'client_secret' =>
                        config(
                            'services.facebook.app_secret'
                        ),

                    'fb_exchange_token' =>
                        $token

                ]

            );


        return
            $response->json();
    }




    /*
    |--------------------------------------------------------------------------
    | DEBUG TOKEN
    |--------------------------------------------------------------------------
    */

    public function debugToken(
        string $token
    ): array {

        $response =
            Http::get(

                $this->baseUrl
                .
                '/debug_token',

                [

                    'input_token' =>
                        $token,

                    'access_token' =>

                        config(
                            'services.facebook.app_id'
                        )

                        .

                        '|'

                        .

                        config(
                            'services.facebook.app_secret'
                        )

                ]

            );


        Log::info(
            'TOKEN DEBUG',
            $response->json()
        );


        return
            $response->json();
    }




    /*
    |--------------------------------------------------------------------------
    | USER PROFILE
    |--------------------------------------------------------------------------
    */

    public function getUserProfile(
        string $token
    ): array {

        $response =
            Http::get(

                $this->baseUrl
                .
                '/me',

                [

                    'fields' =>
                    'id,name,access_token,instagram_business_account'

                ]

            );


        Log::info(
            'USER PROFILE',
            $response->json()
        );


        return
            $response->json();
    }




    /*
    |--------------------------------------------------------------------------
    | GET FACEBOOK PAGES
    |--------------------------------------------------------------------------
    */

    public function getPages(
        string $accessToken
    ): array {

        Log::info(
            '=== GET PAGES ==='
        );


        try {

            $this->debugToken(
                $accessToken
            );


            $response =
                Http::get(

                    $this->baseUrl
                    .
                    '/me/accounts',

                    [

                        'fields' =>

                            'id,name,access_token,instagram_business_account',

                        'access_token' =>

                            $accessToken

                    ]

                );


            Log::info(

                'RAW PAGE RESPONSE',

                $response->json()

            );


            $pages =
                $response->json(
                    'data',
                    []
                );


            Log::info(

                'FACEBOOK PAGES',

                [

                    'pages' =>
                        $pages

                ]

            );


            return
                $pages;
        }

        catch (\Exception $e) {

            Log::error(

                'GET PAGES ERROR: '

                .

                $e->getMessage()

            );


            return [];
        }
    }

        public function fetchConversations($account): array
{
    try {

        $response = Http::get(
            $this->baseUrl . '/' . $account->account_id . '/conversations',
            [
                'fields' =>
'participants,messages.limit(100){id,message,from,created_time}',
                'access_token' =>
                    $account->access_token,
            ]
        );

        Log::info('FB RAW', $response->json());

foreach ($response->json('data', []) as $conversation) {

    foreach (($conversation['messages']['data'] ?? []) as $msg) {

        Log::info('FB MESSAGE', [

            'id' => $msg['id'],

            'text' => $msg['message'] ?? null,

            'created_time' => $msg['created_time'] ?? null,

        ]);
    }
}

        return $response->json('data', []);

    } catch (\Exception $e) {

        Log::error(
            'FB CONVERSATION ERROR: ' .
            $e->getMessage()
        );

        return [];
    } 
}

public function publishPhoto(
    string $pageId,
    string $pageToken,
    string $message,
    string $imageUrl
): array {

    $response = Http::post(

        $this->baseUrl .
        '/' .
        $pageId .
        '/photos',

        [

            'url' =>
                $imageUrl,

            'caption' =>
                $message,

            'access_token' =>
                $pageToken,

        ]

    );

    Log::info(
        'FB PHOTO RESPONSE',
        $response->json()
    );

    return $response->json();
}

public function sendMessage(
    string $recipientId,
    string $message,
    string $token
): array {

    $response = Http::post(
        $this->baseUrl . '/me/messages',
        [
            'recipient' => [
                'id' => $recipientId,
            ],

            'message' => [
                'text' => $message,
            ],

            'messaging_type' => 'RESPONSE',

            'access_token' => $token,
        ]
    );

    if (config('app.debug')) {
    Log::info('FB SEND MESSAGE', [
    'recipient' => $recipientId,
    'response' => $response->json(),
]);
    }

    return $response->json();
}
}