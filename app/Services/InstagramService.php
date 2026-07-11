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
        $version = SettingService::graphVersion();

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
                SettingService::facebookAppId(),

            'redirect_uri' =>
                SettingService::instagramCallbackUrl(),

            'scope' => implode(',', [

                'instagram_basic',

                'instagram_content_publish',

                'instagram_manage_comments',

                'instagram_manage_messages',

                'instagram_manage_insights',

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
    |  Mengambil Instagram Business Account
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
    | MENGAMBIL DATA PROFIL INSTAGRAM
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
    | METRICS — PER MEDIA
    |--------------------------------------------------------------------------
    */

    public function fetchMediaInsights(SocialAccount $account, string $mediaId): array
    {
        try {
            // Read media type, like_count, and comments_count from the media object.
            // like_count: valid for all media types, but returns 0 when the account
            // owner has enabled "Hide Like Counts" in Instagram Settings.
            $media = Http::get($this->baseUrl . '/' . $mediaId, [
                'fields'       => 'media_type,like_count,comments_count',
                'access_token' => $account->access_token,
            ])->json();

            if (isset($media['error'])) {
                Log::warning('IG Media object error', ['media_id' => $mediaId, 'error' => $media['error']]);
            }

            $isReel = ($media['media_type'] ?? '') === 'REELS';

            // For Reels: 'likes' is still available from /insights (not deprecated for Reels).
            // For feed posts: 'likes' was removed from /insights in v18.0; use like_count instead.
            $metrics = $isReel
                ? 'reach,impressions,saved,shares,likes,plays'
                : 'reach,impressions,saved,shares';

            $insightsResponse = Http::get($this->baseUrl . '/' . $mediaId . '/insights', [
                'metric'       => $metrics,
                'access_token' => $account->access_token,
            ])->json();

            if (isset($insightsResponse['error'])) {
                Log::warning('IG Media Insights error', ['media_id' => $mediaId, 'is_reel' => $isReel, 'error' => $insightsResponse['error']]);
            }

            $insights = collect($insightsResponse['data'] ?? [])->pluck('value', 'name')->toArray();

            Log::info('IG Media Insights', ['media_id' => $mediaId, 'media_type' => $media['media_type'] ?? 'unknown', 'insights' => $insights]);

            // For Reels prefer insights 'likes' (API-level count, not affected by hidden-likes UI).
            // For regular posts fall back to like_count from the media object.
            $likes = $isReel
                ? ($insights['likes'] ?? $media['like_count'] ?? 0)
                : ($media['like_count'] ?? 0);

            return [
                'likes'       => $likes,
                'comments'    => $media['comments_count']  ?? 0,
                'reach'       => $insights['reach']        ?? 0,
                'impressions' => $insights['impressions']  ?? 0,
                'saved'       => $insights['saved']        ?? 0,
                'shares'      => $insights['shares']       ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('IG fetchMediaInsights [' . $mediaId . ']: ' . $e->getMessage());
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | METRICS — LEVEL AKUN
    |--------------------------------------------------------------------------
    */

    public function fetchAccountInsights(SocialAccount $account): array
    {
        try {
            // Graph API v22.0 requires explicit since/until for period=day account insights.
            // Without them the API returns an empty data set.
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/insights', [
                'metric'       => 'impressions,reach,profile_views',
                'period'       => 'day',
                'since'        => now()->subDay()->startOfDay()->timestamp,
                'until'        => now()->endOfDay()->timestamp,
                'access_token' => $account->access_token,
            ]);

            $json = $response->json();

            if (isset($json['error'])) {
                Log::warning('IG Account Insights error', ['account' => $account->username, 'error' => $json['error']]);
                return [];
            }

            Log::info('IG Account Insights', ['account' => $account->username, 'data' => $json]);

            return $json['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('IG fetchAccountInsights: ' . $e->getMessage());
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMMENTS
    |--------------------------------------------------------------------------
    */

    public function fetchComments(
    SocialAccount $account,
    string $postId
    
)

: array {
    return Http::get(
        $this->baseUrl . '/' . $postId . '/comments',
        [
            'fields' =>
                'id,text,timestamp,username,like_count',
            'access_token' =>
                $account->access_token,
        ]
    )->json('data', []);
    
}

public function replyComment(
    SocialAccount $account,
    string $commentId,
    string $message
): array {

    return Http::asForm()
        ->post(
            $this->baseUrl . "/{$commentId}/replies",
            [
                'message'      => $message,
                'access_token' => $account->access_token,
            ]
        )
        ->json();
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


    // MENGAMBIL LINK POSTINGAN INSTAGRAM SETELAH PUBLISH KONTEN
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

// MENGIRIM BALASAN DM INSTAGRAM
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