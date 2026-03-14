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
        $version       = config('services.facebook.graph_version', 'v18.0');
        $this->baseUrl = 'https://graph.facebook.com/' . $version;
    }

    // ── OAUTH ───────────────────────────────────────────────────

    /**
     * Step 1: Generate URL redirect ke Instagram OAuth
     * Instagram pakai Facebook OAuth dengan scope instagram_basic
     */
    public function getOAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'     => config('services.facebook.app_id'),
            'redirect_uri'  => config('services.instagram.callback_url'),
            'scope'         => implode(',', [
                'instagram_basic',
                'instagram_content_publish',
                'instagram_manage_comments',
                'instagram_manage_messages',
                'instagram_manage_insights',
                'pages_show_list',
                'pages_read_engagement',
            ]),
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);

        return 'https://www.facebook.com/dialog/oauth?' . $params;
    }

    /**
     * Ambil Instagram Business Account ID dari Facebook Page ID
     */
    public function getInstagramAccountId(string $pageId, string $pageToken): ?string
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $pageId, [
                'fields'       => 'instagram_business_account',
                'access_token' => $pageToken,
            ]);

            return $response->json('instagram_business_account.id');
        } catch (\Exception $e) {
            Log::error('InstagramService::getInstagramAccountId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil info profil Instagram Business Account
     */
    public function getProfile(SocialAccount $account): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $account->account_id, [
                'fields'       => 'id,username,name,biography,followers_count,media_count,profile_picture_url',
                'access_token' => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('InstagramService::getProfile error: ' . $e->getMessage());
            return [];
        }
    }

    // ── PUBLISH ─────────────────────────────────────────────────

    /**
     * Publish single image ke Instagram
     * Instagram pakai 2 step: buat container → publish
     */
    public function publishPhoto(SocialAccount $account, string $imageUrl, string $caption): array
    {
        try {
            // Step 1: Buat media container
            $container = Http::post($this->baseUrl . '/' . $account->account_id . '/media', [
                'image_url'    => $imageUrl,
                'caption'      => $caption,
                'access_token' => $account->access_token,
            ])->json();

            if (!isset($container['id'])) {
                return ['error' => 'Gagal membuat media container: ' . json_encode($container)];
            }

            // Step 2: Tunggu container selesai diproses
            sleep(2);

            // Step 3: Publish container
            $result = Http::post($this->baseUrl . '/' . $account->account_id . '/media_publish', [
                'creation_id'  => $container['id'],
                'access_token' => $account->access_token,
            ])->json();

            return $result;
        } catch (\Exception $e) {
            Log::error('InstagramService::publishPhoto error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Publish video/Reel ke Instagram
     */
    public function publishVideo(SocialAccount $account, string $videoUrl, string $caption): array
    {
        try {
            // Step 1: Buat video container
            $container = Http::post($this->baseUrl . '/' . $account->account_id . '/media', [
                'media_type'   => 'REELS',
                'video_url'    => $videoUrl,
                'caption'      => $caption,
                'access_token' => $account->access_token,
            ])->json();

            if (!isset($container['id'])) {
                return ['error' => 'Gagal membuat video container: ' . json_encode($container)];
            }

            // Step 2: Tunggu video selesai diproses (video butuh lebih lama)
            $this->waitForContainerReady($account, $container['id']);

            // Step 3: Publish
            $result = Http::post($this->baseUrl . '/' . $account->account_id . '/media_publish', [
                'creation_id'  => $container['id'],
                'access_token' => $account->access_token,
            ])->json();

            return $result;
        } catch (\Exception $e) {
            Log::error('InstagramService::publishVideo error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Publish carousel (multi-gambar) ke Instagram
     */
    public function publishCarousel(SocialAccount $account, array $imageUrls, string $caption): array
    {
        try {
            // Step 1: Buat container untuk setiap gambar
            $childIds = [];
            foreach ($imageUrls as $url) {
                $child = Http::post($this->baseUrl . '/' . $account->account_id . '/media', [
                    'image_url'        => $url,
                    'is_carousel_item' => true,
                    'access_token'     => $account->access_token,
                ])->json();

                if (!isset($child['id'])) {
                    return ['error' => 'Gagal membuat carousel item: ' . json_encode($child)];
                }

                $childIds[] = $child['id'];
            }

            // Step 2: Buat carousel container
            $carousel = Http::post($this->baseUrl . '/' . $account->account_id . '/media', [
                'media_type'   => 'CAROUSEL',
                'caption'      => $caption,
                'children'     => implode(',', $childIds),
                'access_token' => $account->access_token,
            ])->json();

            if (!isset($carousel['id'])) {
                return ['error' => 'Gagal membuat carousel container: ' . json_encode($carousel)];
            }

            // Step 3: Tunggu selesai diproses
            sleep(2);

            // Step 4: Publish carousel
            $result = Http::post($this->baseUrl . '/' . $account->account_id . '/media_publish', [
                'creation_id'  => $carousel['id'],
                'access_token' => $account->access_token,
            ])->json();

            return $result;
        } catch (\Exception $e) {
            Log::error('InstagramService::publishCarousel error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ── COMMENTS ────────────────────────────────────────────────

    /**
     * Ambil semua komentar dari sebuah media Instagram
     */
    public function fetchComments(SocialAccount $account, string $mediaId): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $mediaId . '/comments', [
                'fields'       => 'id,text,username,timestamp,like_count,replies{id,text,username,timestamp}',
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('InstagramService::fetchComments error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Balas komentar Instagram
     */
    public function replyComment(SocialAccount $account, string $commentId, string $message): array
    {
        try {
            $response = Http::post($this->baseUrl . '/' . $commentId . '/replies', [
                'message'      => $message,
                'access_token' => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('InstagramService::replyComment error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sembunyikan komentar Instagram
     */
    public function hideComment(SocialAccount $account, string $commentId): bool
    {
        try {
            $response = Http::post($this->baseUrl . '/' . $commentId, [
                'hide'         => true,
                'access_token' => $account->access_token,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('InstagramService::hideComment error: ' . $e->getMessage());
            return false;
        }
    }

    // ── MESSAGES (DM) ───────────────────────────────────────────

    /**
     * Ambil semua DM Instagram
     */
    public function fetchMessages(SocialAccount $account): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/conversations', [
                'platform'     => 'instagram',
                'fields'       => 'id,messages{id,text,from,created_time},participants',
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('InstagramService::fetchMessages error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kirim balasan DM Instagram
     */
    public function sendMessage(SocialAccount $account, string $recipientId, string $message): array
    {
        try {
            $response = Http::post($this->baseUrl . '/me/messages', [
                'recipient'    => ['id' => $recipientId],
                'message'      => ['text' => $message],
                'access_token' => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('InstagramService::sendMessage error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ── INSIGHTS ────────────────────────────────────────────────

    /**
     * Ambil insights akun Instagram (followers, reach, impressions)
     */
    public function fetchAccountInsights(SocialAccount $account, string $period = 'day'): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/insights', [
                'metric'       => implode(',', [
                    'impressions',
                    'reach',
                    'profile_views',
                    'follower_count',
                ]),
                'period'       => $period,
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('InstagramService::fetchAccountInsights error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil insights dari sebuah media Instagram (likes, comments, shares, saves, reach)
     */
    public function fetchMediaInsights(SocialAccount $account, string $mediaId): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $mediaId . '/insights', [
                'metric'       => 'impressions,reach,likes,comments,shares,saved,plays',
                'access_token' => $account->access_token,
            ]);

            $data   = $response->json('data', []);
            $result = [];

            foreach ($data as $metric) {
                $result[$metric['name']] = $metric['values'][0]['value'] ?? 0;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('InstagramService::fetchMediaInsights error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua media dari akun Instagram
     */
    public function fetchMedia(SocialAccount $account, int $limit = 20): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/media', [
                'fields'       => 'id,caption,media_type,media_url,thumbnail_url,timestamp,like_count,comments_count',
                'limit'        => $limit,
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('InstagramService::fetchMedia error: ' . $e->getMessage());
            return [];
        }
    }

    // ── HELPERS ─────────────────────────────────────────────────

    /**
     * Tunggu container selesai diproses (untuk video)
     * Poll status setiap 5 detik, max 60 detik
     */
    private function waitForContainerReady(SocialAccount $account, string $containerId, int $maxWait = 60): void
    {
        $waited = 0;

        while ($waited < $maxWait) {
            sleep(5);
            $waited += 5;

            $status = Http::get($this->baseUrl . '/' . $containerId, [
                'fields'       => 'status_code',
                'access_token' => $account->access_token,
            ])->json('status_code');

            if ($status === 'FINISHED') break;
            if ($status === 'ERROR')    break;
        }
    }
}