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
        $version = config(
            'services.facebook.graph_version',
            'v22.0'
        );

        $this->baseUrl =
            'https://graph.facebook.com/' . $version;
    }

    /*
    |--------------------------------------------------------------------------
    | OAUTH URL
    |--------------------------------------------------------------------------
    */

    public function getOAuthUrl(): string
    {
        $params = http_build_query([

            'client_id' =>
                config('services.facebook.app_id'),

            'redirect_uri' =>
                config('services.instagram.callback_url'),

            'scope' => implode(',', [

                'instagram_basic',

                'instagram_content_publish',

                'instagram_manage_comments',

                'instagram_manage_messages',

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
    | GET INSTAGRAM ACCOUNT ID
    |--------------------------------------------------------------------------
    */

    public function getInstagramAccountId(
        string $pageId,
        string $pageToken
    ): ?string {

        try {

            $response = Http::get(
                $this->baseUrl . '/' . $pageId,
                [

                    'fields' =>
                        'instagram_business_account',

                    'access_token' =>
                        $pageToken,
                ]
            );

            return $response->json(
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

            Log::error($e->getMessage());

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLISH SINGLE PHOTO
    |--------------------------------------------------------------------------
    */

    public function publishPhoto(
        SocialAccount $account,
        string $imageUrl,
        string $caption
    ): array {

        try {

            /*
            |--------------------------------------------------------------------------
            | CREATE CONTAINER
            |--------------------------------------------------------------------------
            */

            $container = Http::post(

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

            /*
            |--------------------------------------------------------------------------
            | VALIDASI CONTAINER
            |--------------------------------------------------------------------------
            */

            if (!isset($container['id'])) {

                throw new \Exception(

                    $container['error']['message']

                    ?? 'Gagal membuat Instagram media container.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DELAY
            |--------------------------------------------------------------------------
            */

            sleep(5);

            /*
            |--------------------------------------------------------------------------
            | PUBLISH
            |--------------------------------------------------------------------------
            */

            $publish = Http::post(

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

            /*
            |--------------------------------------------------------------------------
            | VALIDASI PUBLISH
            |--------------------------------------------------------------------------
            */

            if (!isset($publish['id'])) {

                throw new \Exception(

                    $publish['error']['message']

                    ?? 'Gagal publish Instagram post.'
                );
            }

            return $publish;

        } catch (\Exception $e) {

            Log::error(
                'IG Publish Photo Error: '
                    . $e->getMessage()
            );

            return [

                'error' => [
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLISH CAROUSEL
    |--------------------------------------------------------------------------
    */

    public function publishCarousel(
        SocialAccount $account,
        array $mediaUrls,
        string $caption
    ): array {

        try {

            $children = [];

            /*
            |--------------------------------------------------------------------------
            | CREATE CHILD CONTAINER
            |--------------------------------------------------------------------------
            */

            foreach ($mediaUrls as $url) {

                $child = Http::post(

                    $this->baseUrl
                        . '/'
                        . $account->account_id
                        . '/media',

                    [

                        'image_url' =>
                            $url,

                        'is_carousel_item' =>
                            true,

                        'access_token' =>
                            $account->access_token,
                    ]

                )->json();

                if (!isset($child['id'])) {

                    throw new \Exception(

                        $child['error']['message']

                        ?? 'Gagal membuat child container.'
                    );
                }

                $children[] = $child['id'];
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE CAROUSEL CONTAINER
            |--------------------------------------------------------------------------
            */

            $container = Http::post(

                $this->baseUrl
                    . '/'
                    . $account->account_id
                    . '/media',

                [

                    'media_type' =>
                        'CAROUSEL',

                    'children' =>
                        implode(',', $children),

                    'caption' =>
                        $caption,

                    'access_token' =>
                        $account->access_token,
                ]

            )->json();

            if (!isset($container['id'])) {

                throw new \Exception(

                    $container['error']['message']

                    ?? 'Gagal membuat carousel container.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DELAY
            |--------------------------------------------------------------------------
            */

            sleep(5);

            /*
            |--------------------------------------------------------------------------
            | PUBLISH CAROUSEL
            |--------------------------------------------------------------------------
            */

            $publish = Http::post(

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

            /*
            |--------------------------------------------------------------------------
            | VALIDASI PUBLISH
            |--------------------------------------------------------------------------
            */

            if (!isset($publish['id'])) {

                throw new \Exception(

                    $publish['error']['message']

                    ?? 'Gagal publish carousel Instagram.'
                );
            }

            return $publish;

        } catch (\Exception $e) {

            Log::error(
                'IG Carousel Error: '
                    . $e->getMessage()
            );

            return [

                'error' => [
                    'message' => $e->getMessage()
                ]
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

    /*
    |--------------------------------------------------------------------------
    | GET PROFILE BY ID
    |--------------------------------------------------------------------------
    */

    public function getProfileById(
        string $igId,
        string $token
    ): array {

        return Http::get(

            $this->baseUrl
                . '/'
                . $igId,

            [

                'fields' =>
                    'id,username,name',

                'access_token' =>
                    $token
            ]

        )->json();
    }

public function getPostPermalink(
    string $mediaId,
    string $token
): ?string {

    $response = Http::get(

        $this->baseUrl .
        '/' .
        $mediaId,

        [

            'fields' =>
                'permalink',

            'access_token' =>
                $token,

        ]

    )->json();

    return $response['permalink'] ?? null;
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

    Log::info('IG SEND MESSAGE', [
        'response' => $response->json(),
    ]);

    return $response->json();
}

public function getUsername(
    string $userId,
    string $token
): ?string {

    try {

        $response = Http::get(
            $this->baseUrl . '/' . $userId,
            [
                'fields' =>
                    'username,name',

                'access_token' =>
                    $token,
            ]
        );

        Log::info(
            'IG USER PROFILE',
            $response->json()
        );

        return
            $response->json('username')
            ??
            $response->json('name');

    } catch (\Exception $e) {

        Log::error(
            'GET USERNAME ERROR: '
            . $e->getMessage()
        );

        return null;
    }
}
}