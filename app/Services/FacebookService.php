<?php

namespace App\Services;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    private string $baseUrl;
    private string $version;

    public function __construct()
    {
        $this->version = config('services.facebook.graph_version', 'v18.0');
        $this->baseUrl = 'https://graph.facebook.com/' . $this->version;
    }

    // ── OAUTH ───────────────────────────────────────────────────

    /**
     * Step 1: Generate URL redirect ke Facebook OAuth
     */
    public function getOAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'     => config('services.facebook.app_id'),
            'redirect_uri'  => config('services.facebook.callback_url'),
            'scope'         => implode(',', [
                'pages_manage_posts',
                'pages_read_engagement',
                'pages_messaging',
                'pages_show_list',
                'read_insights',
                'public_profile',
            ]),
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);

        return 'https://www.facebook.com/dialog/oauth?' . $params;
    }

    /**
     * Step 2: Tukar authorization code dengan short-lived token
     */
    public function getShortLivedToken(string $code): array
    {
        $response = Http::get($this->baseUrl . '/oauth/access_token', [
            'client_id'     => config('services.facebook.app_id'),
            'client_secret' => config('services.facebook.app_secret'),
            'redirect_uri'  => config('services.facebook.callback_url'),
            'code'          => $code,
        ]);

        return $response->json();
    }

    /**
     * Step 3: Tukar short-lived token dengan long-lived token (~60 hari)
     */
    public function getLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get($this->baseUrl . '/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.facebook.app_id'),
            'client_secret'     => config('services.facebook.app_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        return $response->json();
    }

    /**
     * Ambil semua Facebook Pages yang dikelola user
     */
    public function getPages(string $userToken): array
    {
        $response = Http::get($this->baseUrl . '/me/accounts', [
            'fields'       => 'id,name,access_token,category,fan_count',
            'access_token' => $userToken,
        ]);

        return $response->json('data', []);
    }

    /**
     * Ambil info profil user Facebook
     */
    public function getUserProfile(string $token): array
    {
        $response = Http::get($this->baseUrl . '/me', [
            'fields'       => 'id,name,email,picture',
            'access_token' => $token,
        ]);

        return $response->json();
    }

    // ── POST ────────────────────────────────────────────────────

    /**
     * Publish text post ke Facebook Page
     */
    public function publishTextPost(SocialAccount $account, string $message): array
    {
        try {
            $response = Http::post($this->baseUrl . '/' . $account->account_id . '/feed', [
                'message'      => $message,
                'access_token' => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FacebookService::publishTextPost error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Publish post dengan satu foto ke Facebook Page
     */
    public function publishPhotoPost(SocialAccount $account, string $message, string $photoUrl): array
    {
        try {
            $response = Http::post($this->baseUrl . '/' . $account->account_id . '/photos', [
                'message'      => $message,
                'url'          => $photoUrl,
                'access_token' => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FacebookService::publishPhotoPost error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Publish post dengan banyak foto (album) ke Facebook Page
     */
    public function publishMultiplePhotos(SocialAccount $account, string $message, array $photoUrls): array
    {
        try {
            // Upload semua foto tanpa publish dulu
            $mediaIds = [];
            foreach ($photoUrls as $url) {
                $upload = Http::post($this->baseUrl . '/' . $account->account_id . '/photos', [
                    'url'          => $url,
                    'published'    => false,
                    'access_token' => $account->access_token,
                ]);
                $mediaIds[] = ['media_fbid' => $upload->json('id')];
            }

            // Publish semua sekaligus dalam 1 post
            $response = Http::post($this->baseUrl . '/' . $account->account_id . '/feed', [
                'message'       => $message,
                'attached_media' => json_encode($mediaIds),
                'access_token'  => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FacebookService::publishMultiplePhotos error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ── COMMENTS ────────────────────────────────────────────────

    /**
     * Ambil semua komentar dari sebuah post
     */
    public function fetchComments(SocialAccount $account, string $postId): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $postId . '/comments', [
                'fields'       => 'id,message,from,created_time,like_count',
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('FacebookService::fetchComments error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Balas komentar
     */
    public function replyComment(SocialAccount $account, string $commentId, string $message): array
    {
        try {
            $response = Http::post($this->baseUrl . '/' . $commentId . '/comments', [
                'message'      => $message,
                'access_token' => $account->access_token,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('FacebookService::replyComment error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ── MESSAGES (MESSENGER) ────────────────────────────────────

    /**
     * Ambil semua conversation dari Messenger
     */
    public function fetchConversations(SocialAccount $account): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/conversations', [
                'fields'       => 'id,snippet,updated_time,message_count,unread_count,participants',
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('FacebookService::fetchConversations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kirim pesan balasan via Messenger
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
            Log::error('FacebookService::sendMessage error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // ── INSIGHTS ────────────────────────────────────────────────

    /**
     * Ambil page insights (reach, impressions, engagement)
     */
    public function fetchPageInsights(SocialAccount $account, string $period = 'day'): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $account->account_id . '/insights', [
                'metric'       => implode(',', [
                    'page_impressions',
                    'page_reach',
                    'page_engaged_users',
                    'page_post_engagements',
                    'page_fan_count',
                ]),
                'period'       => $period,
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('FacebookService::fetchPageInsights error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil insights dari sebuah post (likes, comments, shares, reach)
     */
    public function fetchPostInsights(SocialAccount $account, string $postId): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $postId . '/insights', [
                'metric'       => 'post_impressions,post_reach,post_engaged_users,post_clicks',
                'access_token' => $account->access_token,
            ]);

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('FacebookService::fetchPostInsights error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil jumlah likes, comments, shares dari sebuah post
     */
    public function fetchPostEngagement(SocialAccount $account, string $postId): array
    {
        try {
            $response = Http::get($this->baseUrl . '/' . $postId, [
                'fields'       => 'likes.summary(true),comments.summary(true),shares',
                'access_token' => $account->access_token,
            ]);

            $data = $response->json();

            return [
                'likes'    => $data['likes']['summary']['total_count'] ?? 0,
                'comments' => $data['comments']['summary']['total_count'] ?? 0,
                'shares'   => $data['shares']['count'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('FacebookService::fetchPostEngagement error: ' . $e->getMessage());
            return ['likes' => 0, 'comments' => 0, 'shares' => 0];
        }
    }

    // ── TOKEN ───────────────────────────────────────────────────

    /**
     * Cek apakah token masih valid
     */
    public function verifyToken(string $token): bool
    {
        try {
            $response = Http::get($this->baseUrl . '/debug_token', [
                'input_token'  => $token,
                'access_token' => config('services.facebook.app_id') . '|' . config('services.facebook.app_secret'),
            ]);

            return $response->json('data.is_valid', false);
        } catch (\Exception $e) {
            return false;
        }
    }
}