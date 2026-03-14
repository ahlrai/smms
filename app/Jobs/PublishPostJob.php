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

    public int $tries   = 3;       // Coba ulang maksimal 3x kalau gagal
    public int $backoff = 60;      // Tunggu 60 detik sebelum retry
    public int $timeout = 120;     // Timeout 2 menit

    public function __construct(private Post $post) {}

    public function handle(FacebookService $fb, InstagramService $ig): void
    {
        $account   = $this->post->socialAccount;
        $mediaUrls = $this->post->media->pluck('file_path')->map(fn ($path) =>
            asset('storage/' . $path)
        )->toArray();

        try {
            // Cek token expired
            if ($account->isTokenExpired()) {
                throw new \Exception('Token akun ' . $account->username . ' sudah expired.');
            }

            $result = [];

            if ($account->platform === 'facebook') {
                // Publish ke Facebook
                if (empty($mediaUrls)) {
                    $result = $fb->publishTextPost($account, $this->post->caption);
                } elseif (count($mediaUrls) === 1) {
                    $result = $fb->publishPhotoPost($account, $this->post->caption, $mediaUrls[0]);
                } else {
                    $result = $fb->publishMultiplePhotos($account, $this->post->caption, $mediaUrls);
                }
            } else {
                // Publish ke Instagram
                if (empty($mediaUrls)) {
                    throw new \Exception('Instagram membutuhkan minimal 1 gambar/video.');
                } elseif (count($mediaUrls) === 1) {
                    $result = $ig->publishPhoto($account, $mediaUrls[0], $this->post->caption);
                } else {
                    $result = $ig->publishCarousel($account, $mediaUrls, $this->post->caption);
                }
            }

            // Cek apakah ada error dari API
            if (isset($result['error'])) {
                throw new \Exception($result['error']['message'] ?? json_encode($result['error']));
            }

            // Tandai post sebagai published
            $this->post->markAsPublished($result['id'] ?? 'unknown');

            // Kirim notifikasi sukses ke creator
            CustomNotification::notifyUser(
                $this->post->created_by,
                'Post Berhasil Dipublish! ✅',
                'Post "' . substr($this->post->caption, 0, 50) . '..." berhasil dipublish ke ' . ucfirst($account->platform),
                'success',
                '/admin/posts'
            );

            Log::info('PublishPostJob berhasil: Post ID ' . $this->post->id);

        } catch (\Exception $e) {
            // Tandai post sebagai failed
            $this->post->markAsFailed($e->getMessage());

            // Kirim notifikasi error ke creator
            CustomNotification::notifyUser(
                $this->post->created_by,
                'Post Gagal Dipublish ❌',
                'Post "' . substr($this->post->caption, 0, 50) . '..." gagal: ' . $e->getMessage(),
                'error',
                '/admin/posts'
            );

            Log::error('PublishPostJob gagal (Post ID: ' . $this->post->id . '): ' . $e->getMessage());

            // Lempar exception agar queue bisa retry
            $this->fail($e);
        }
    }

    /**
     * Dipanggil setelah semua retry habis dan masih gagal
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishPostJob final failed (Post ID: ' . $this->post->id . '): ' . $exception->getMessage());

        CustomNotification::notifyAdmins(
            'Post Gagal Setelah 3x Percobaan ❌',
            'Post ID ' . $this->post->id . ' gagal dipublish setelah 3x retry: ' . $exception->getMessage(),
            'error',
            '/admin/posts'
        );
    }
}