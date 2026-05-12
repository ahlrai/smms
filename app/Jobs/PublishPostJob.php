<?php

namespace App\Jobs;

use App\Models\CustomNotification;
use App\Models\Post;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(private Post $post) {}

    public function handle(FacebookService $fb, InstagramService $ig): void
    {
        // Refresh data terbaru dari database
        $this->post->refresh();

        // Kalau sudah published, stop
        if ($this->post->status === 'published') {
            return;
        }

        $account = $this->post->socialAccount;

        $mediaUrls = $this->post->media()
        ->orderBy('id', 'asc')
        ->get()
        ->pluck('file_path')
        ->map(fn ($path) => asset('storage/' . $path))
        ->toArray();

        try {

            // =========================
            // CEK TOKEN EXPIRED
            // =========================
            if ($account->isTokenExpired()) {
                throw new \Exception(
                    'Token akun ' . $account->username . ' sudah expired.'
                );
            }

            $result = [];

            // =========================
            // FACEBOOK
            // =========================
            if ($account->platform === 'facebook') {

                if (empty($mediaUrls)) {

                    $result = $fb->publishTextPost(
                        $account,
                        $this->post->caption
                    );

                } elseif (count($mediaUrls) === 1) {

                    $result = $fb->publishPhotoPost(
                        $account,
                        $this->post->caption,
                        $mediaUrls[0]
                    );

                } else {

                    $result = $fb->publishMultiplePhotos(
                        $account,
                        $this->post->caption,
                        $mediaUrls
                    );
                }

            } else {

                // =========================
                // INSTAGRAM
                // =========================
                if (empty($mediaUrls)) {
                    throw new \Exception(
                        'Instagram membutuhkan minimal 1 gambar/video.'
                    );
                }

                if (count($mediaUrls) === 1) {

                    $result = $ig->publishPhoto(
                        $account,
                        $mediaUrls[0],
                        $this->post->caption
                    );

                } else {

                    $result = $ig->publishCarousel(
                        $account,
                        $mediaUrls,
                        $this->post->caption
                    );
                }
            }

            // =========================
            // VALIDASI ERROR API
            // =========================
            if (isset($result['error'])) {
                throw new \Exception(
                    $result['error']['message']
                    ?? json_encode($result['error'])
                );
            }

            // =========================
            // UPDATE STATUS PUBLISHED
            // =========================
            $this->post->update([
            'status' => 'published',
            'published_at' => now(),
            ]);

            // =========================
            // NOTIF SUCCESS
            // =========================
            CCustomNotification::notifyUser(
            $this->post->created_by,
            'Post Berhasil Dipublish ✅',
            'Post "' . substr($this->post->caption, 0, 50) . '..." berhasil dipublish ke ' . ucfirst($account->platform),
            'success',
            '/admin/posts'
            );

            Log::info(
                'PublishPostJob berhasil: Post ID ' . $this->post->id
            );

        } catch (\Exception $e) {

            // =========================
            // UPDATE STATUS FAILED
            // =========================
            $this->post->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // =========================
            // NOTIF FAILED
            // =========================
            CustomNotification::notifyUser(
                $this->post->created_by,
                'Post Gagal Dipublish ❌',
                'Post "' .
                substr($this->post->caption, 0, 50) .
                '..." gagal: ' .
                $e->getMessage(),
                'error',
                '/admin/posts'
            );

            Log::error(
                'PublishPostJob gagal (Post ID: ' .
                $this->post->id .
                '): ' .
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Dipanggil setelah retry habis
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            'PublishPostJob FINAL FAILED (Post ID: ' .
            $this->post->id .
            '): ' .
            $exception->getMessage()
        );

        CustomNotification::notifyAdmins(
            'Post Gagal Setelah 3x Percobaan ❌',
            'Post ID ' .
            $this->post->id .
            ' gagal dipublish setelah 3x retry: ' .
            $exception->getMessage(),
            'error',
            '/admin/posts'
        );
    }
}