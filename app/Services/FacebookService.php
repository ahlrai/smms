<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SocialAccount;

class FacebookService
{
    private string $baseUrl;
    private string $version;


    public function __construct()
    {
        $this->version =
            SettingService::get(
                'facebook_graph_version',
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

    public function getOAuthUrl(): string
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
        'pages_messaging',
        'read_insights',          // required for /{page-id}/insights

        // Instagram
        'instagram_basic',
        'instagram_content_publish',
        'instagram_manage_comments',
        'instagram_manage_messages',
        'instagram_manage_insights',

        // Optional
        'business_management',

        // Default
        'public_profile',
    ];


    $params = [

        'client_id' =>
            SettingService::get('facebook_app_id'),

        'redirect_uri' =>
            SettingService::get('instagram_callback_url'),

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

            SettingService::get('instagram_callback_url')

            :

            SettingService::get('facebook_callback_url');


        $response =
            Http::get(

                'https://graph.facebook.com/oauth/access_token',

                [

                    'client_id' =>
                        SettingService::get('facebook_app_id'),

                    'client_secret' =>
                        SettingService::get('facebook_app_secret'),

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
                        SettingService::get('facebook_app_id'),

                    'client_secret' =>
                        SettingService::get('facebook_app_secret'),

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

                        SettingService::get('facebook_app_id'),

                        '|'

                        .

                        SettingService::get('facebook_app_secret'),


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

                            'id,name,access_token,instagram_business_account{id,username,name}',

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

    /*
    |--------------------------------------------------------------------------
    | FETCH COMMENTS ON A POST
    |--------------------------------------------------------------------------
    */

    public function fetchComments(SocialAccount $account, string $postId): array
    {
        try {
            $response = Http::get(
                $this->baseUrl . '/' . $postId . '/comments',
                [
                    'fields'       => 'id,message,from,created_time,like_count',
                    'access_token' => $account->access_token,
                ]
            );

            Log::info('FB FETCH COMMENTS', ['post_id' => $postId, 'data' => $response->json()]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('FB fetchComments [' . $postId . ']: ' . $e->getMessage());
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REPLY TO COMMENT
    |--------------------------------------------------------------------------
    */

    public function replyComment(
        SocialAccount $account,
        string $commentId,
        string $message
    ): array {
        try {
            $response = Http::asForm()->post(
                $this->baseUrl . "/{$commentId}/comments",
                [
                    'message'      => $message,
                    'access_token' => $account->access_token,
                ]
            );

            Log::info('FB REPLY COMMENT', $response->json());

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FB REPLY ERROR: ' . $e->getMessage());
            return ['error' => ['message' => $e->getMessage()]];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METRICS — PER POST
    |--------------------------------------------------------------------------
    */

    public function fetchPostEngagement(
    SocialAccount $account,
    string $postId
): array {

    try {

        $response = Http::get(
            $this->baseUrl.'/'.$postId,
            [
                'fields' =>
                    'id,reactions.summary(total_count),comments.summary(true),shares',

                'access_token' =>
                    $account->access_token,
            ]
        );


        $data = $response->json();
        Log::info('RAW FB ENGAGEMENT', $data);


        Log::info('FB POST ENGAGEMENT', [
            'post_id' => $postId,
            'data' => $data
        ]);


        if (isset($data['error'])) {

            Log::warning(
                'FB ENGAGEMENT ERROR',
                $data['error']
            );

            return [];
        }


        return [

            'likes' =>
                $data['reactions']['summary']['total_count'] ?? 0,


            'comments' =>
                $data['comments']['summary']['total_count'] ?? 0,


            'shares' =>
                $data['shares']['count'] ?? 0,

        ];


    } catch (\Exception $e) {


        Log::error(
            'FB fetchPostEngagement: '.$e->getMessage()
        );


        return [];
    }
}
    /*
    |--------------------------------------------------------------------------
    | METRICS — LEVEL PAGE
    |--------------------------------------------------------------------------
    */

    public function fetchPageInsights(SocialAccount $account): array
    {
        try {
            // page_post_engagements was deprecated in v18.0.
            // page_impressions and page_impressions_unique require the read_insights OAuth scope.
            // since/until are required for day-period metrics in v22.0.
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/insights', [
                'metric'       => 'page_impressions,page_impressions_unique,page_fans',
                'period'       => 'day',
                'since'        => now()->subDay()->startOfDay()->timestamp,
                'until'        => now()->endOfDay()->timestamp,
                'access_token' => $account->access_token,
            ]);

            $json = $response->json();

            if (isset($json['error'])) {
                Log::warning('FB Page Insights error', ['account' => $account->username, 'error' => $json['error']]);
                return [];
            }

            Log::info('FB Page Insights', ['account' => $account->username, 'data' => $json]);

            return $json['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('FB fetchPageInsights: ' . $e->getMessage());
            return [];
        }
    }
    /*
    |--------------------------------------------------------------------------
    | SEND DM (Messenger)
    |--------------------------------------------------------------------------
    */

    public function sendMessage(string $recipientId, string $text, string $pageToken): array
    {
        try {
            $response = Http::post(
                $this->baseUrl . '/me/messages',
                [
                    'recipient'    => ['id' => $recipientId],
                    'message'      => ['text' => $text],
                    'access_token' => $pageToken,
                ]
            );

            Log::info('FB SEND MESSAGE', $response->json());

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FB SEND MESSAGE ERROR: ' . $e->getMessage());
            return ['error' => ['message' => $e->getMessage()]];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONVERSATIONS
    |--------------------------------------------------------------------------
    */

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
}