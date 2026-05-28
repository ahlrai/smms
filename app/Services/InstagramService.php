<?php

namespace App\Services;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private string $baseUrl;

    public function __construct()
    {
        $version = config('services.facebook.graph_version', 'v22.0');

        $this->baseUrl =
            'https://graph.facebook.com/' . $version;
    }

    /*
    |--------------------------------------------------------------------------
    | OAUTH INSTAGRAM
    |--------------------------------------------------------------------------
    */

    public function getOAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => config('services.facebook.app_id'),

            'redirect_uri' =>
                config('services.instagram.callback_url'),

            'scope' => implode(',', [

                // WAJIB
                'instagram_basic',

                // publish konten
                'instagram_content_publish',

                // komentar
                'instagram_manage_comments',

                // DM
                'instagram_manage_messages',

                // akses halaman FB
                'pages_show_list',

            ]),

            'response_type' => 'code',

            'state' => csrf_token(),
        ]);

        return
            'https://www.facebook.com/dialog/oauth?'
            . $params;
    }


    /*
    |--------------------------------------------------------------------------
    | GET IG ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function getInstagramAccountId(
        string $pageId,
        string $pageToken
    ): ?string {

        try {

            $response =
                Http::get(
                    $this->baseUrl . '/' . $pageId,
                    [

                        'fields' =>
                            'instagram_business_account',

                        'access_token' =>
                            $pageToken,

                    ]
                );

            return
                $response->json(
                    'instagram_business_account.id'
                );

        } catch (\Exception $e) {

            Log::error(
                'IG Account Error: '
                . $e->getMessage()
            );

            return null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    public function getProfile(
        SocialAccount $account
    ): array {

        try {

            return Http::get(
                $this->baseUrl
                . '/'
                . $account->account_id,

                [

                    'fields' =>
                        'id,username,name,followers_count,media_count',

                    'access_token' =>
                        $account->access_token,

                ]

            )->json();

        } catch (\Exception $e) {

            Log::error(
                $e->getMessage()
            );

            return [];
        }
    }



    /*
    |--------------------------------------------------------------------------
    | PUBLISH IMAGE
    |--------------------------------------------------------------------------
    */

    public function publishPhoto(
        SocialAccount $account,
        string $imageUrl,
        string $caption
    ): array {

        try {

            $container =
                Http::post(

                    $this->baseUrl
                    . '/'
                    . $account->account_id
                    . '/media',

                    [

                        'image_url' =>
                            $imageUrl,

                        'caption' =>
                            $caption,

                        'access_token' =>
                            $account->access_token,

                    ]

                )->json();


            if (!isset($container['id'])) {

                return [
                    'error' =>
                        'Container gagal dibuat'
                ];
            }


            sleep(2);


            return Http::post(

                $this->baseUrl
                . '/'
                . $account->account_id
                . '/media_publish',

                [

                    'creation_id' =>
                        $container['id'],

                    'access_token' =>
                        $account->access_token,

                ]

            )->json();

        }

        catch (\Exception $e) {

            return [

                'error' =>
                    $e->getMessage()

            ];
        }
    }



    /*
    |--------------------------------------------------------------------------
    | COMMENTS
    |--------------------------------------------------------------------------
    */

    public function fetchComments(
        SocialAccount $account,
        string $mediaId
    ): array {

        return Http::get(

            $this->baseUrl
            . '/'
            . $mediaId
            . '/comments',

            [

                'fields' =>
                    'id,text,username',

                'access_token' =>
                    $account->access_token,

            ]

        )->json('data', []);
    }



    /*
    |--------------------------------------------------------------------------
    | DM
    |--------------------------------------------------------------------------
    */

    public function fetchMessages(
        SocialAccount $account
    ): array {

        return Http::get(

            $this->baseUrl
            . '/'
            . $account->account_id
            . '/conversations',

            [

                'platform' =>
                    'instagram',

                'fields' =>
                    'messages',

                'access_token' =>
                    $account->access_token,

            ]

        )->json('data', []);
    }



    /*
    |--------------------------------------------------------------------------
    | MEDIA
    |--------------------------------------------------------------------------
    */

    public function fetchMedia(
        SocialAccount $account
    ): array {

        return Http::get(

            $this->baseUrl
            . '/'
            . $account->account_id
            . '/media',

            [

                'fields' =>
                    'id,caption,media_url,media_type,timestamp',

                'access_token' =>
                    $account->access_token,

            ]

        )->json('data', []);
    }

    public function getProfileById(
    string $igId,
    string $token
): array {

    return Http::get(

        $this->baseUrl .
        '/' .
        $igId,

        [

            'fields' =>
            'id,username,name',

            'access_token' =>
            $token

        ]

    )->json();
}

}