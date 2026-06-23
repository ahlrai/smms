<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\CustomNotification;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\Metric;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserSocialPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. STAFF USERS ─────────────────────────────────────────────────
        $staff1 = User::firstOrCreate(
            ['email' => 'staff1@example.com'],
            ['name' => 'Budi Santoso', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $staff1->assignRole('staff');

        $staff2 = User::firstOrCreate(
            ['email' => 'staff2@example.com'],
            ['name' => 'Siti Rahayu', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $staff2->assignRole('staff');

        $admin = User::whereEmail('admin@example.com')->first();

        // ── 2. SOCIAL ACCOUNTS ─────────────────────────────────────────────
        $fbAccount = SocialAccount::firstOrCreate(
            ['platform' => 'facebook', 'account_id' => 'fb_page_001'],
            [
                'username'         => 'Toko Maju Jaya',
                'access_token'     => 'demo_fb_token_' . str_repeat('x', 32),
                'refresh_token'    => null,
                'token_expired_at' => now()->addDays(55),
                'created_by'       => $admin->id,
            ]
        );

        $igAccount = SocialAccount::firstOrCreate(
            ['platform' => 'instagram', 'account_id' => 'ig_biz_001'],
            [
                'username'         => '@toko_maju_jaya',
                'access_token'     => 'demo_ig_token_' . str_repeat('y', 32),
                'refresh_token'    => null,
                'token_expired_at' => now()->addDays(50),
                'created_by'       => $admin->id,
            ]
        );

        // ── 3. USER SOCIAL PERMISSIONS ─────────────────────────────────────
        foreach ([$fbAccount, $igAccount] as $account) {
            UserSocialPermission::firstOrCreate(
                ['user_id' => $staff1->id, 'social_account_id' => $account->id],
                [
                    'can_view'           => true,
                    'can_create_post'    => true,
                    'can_schedule_post'  => true,
                    'can_publish_post'   => false,
                    'can_reply_comment'  => true,
                    'can_reply_message'  => true,
                    'can_view_analytics' => true,
                ]
            );
            UserSocialPermission::firstOrCreate(
                ['user_id' => $staff2->id, 'social_account_id' => $account->id],
                [
                    'can_view'           => true,
                    'can_create_post'    => true,
                    'can_schedule_post'  => false,
                    'can_publish_post'   => false,
                    'can_reply_comment'  => true,
                    'can_reply_message'  => false,
                    'can_view_analytics' => true,
                ]
            );
        }

        // ── 4. POSTS ───────────────────────────────────────────────────────
        $postsData = [
            // Facebook — published
            [
                'social_account_id' => $fbAccount->id,
                'platform'          => 'facebook',
                'caption'           => 'Promo spesial akhir bulan! Diskon 20% untuk semua produk unggulan kami. Jangan sampai ketinggalan! 🎉 #PromoMaret #TokoMajuJaya',
                'status'            => 'published',
                'platform_post_id'  => 'fb_post_101',
                'published_at'      => now()->subDays(6),
                'scheduled_at'      => null,
                'created_by'        => $admin->id,
            ],
            [
                'social_account_id' => $fbAccount->id,
                'platform'          => 'facebook',
                'caption'           => 'Terima kasih telah mempercayai produk kami selama ini. Kami terus berkomitmen untuk memberikan kualitas terbaik! 🙏',
                'status'            => 'published',
                'platform_post_id'  => 'fb_post_102',
                'published_at'      => now()->subDays(3),
                'scheduled_at'      => null,
                'created_by'        => $staff1->id,
            ],
            // Instagram — published
            [
                'social_account_id' => $igAccount->id,
                'platform'          => 'instagram',
                'caption'           => 'New arrival! Koleksi terbaru kami sudah tersedia. Swipe untuk lihat semua pilihan warna! 😍✨ #NewArrival #Fashion',
                'status'            => 'published',
                'platform_post_id'  => 'ig_media_201',
                'published_at'      => now()->subDays(5),
                'scheduled_at'      => null,
                'created_by'        => $staff1->id,
            ],
            [
                'social_account_id' => $igAccount->id,
                'platform'          => 'instagram',
                'caption'           => 'Behind the scene proses produksi kami. Setiap detail dikerjakan dengan penuh cinta ❤️ #BehindTheScene #Handmade',
                'status'            => 'published',
                'platform_post_id'  => 'ig_media_202',
                'published_at'      => now()->subDays(2),
                'scheduled_at'      => null,
                'created_by'        => $staff2->id,
            ],
            // Scheduled
            [
                'social_account_id' => $fbAccount->id,
                'platform'          => 'facebook',
                'caption'           => 'Flash Sale besok mulai pukul 10.00 WIB! Set alarm kalian sekarang 🔔 #FlashSale #LimitedStock',
                'status'            => 'scheduled',
                'platform_post_id'  => null,
                'published_at'      => null,
                'scheduled_at'      => now()->addDays(1)->setHour(10),
                'created_by'        => $staff1->id,
            ],
            [
                'social_account_id' => $igAccount->id,
                'platform'          => 'instagram',
                'caption'           => 'Weekend special! Gratis ongkir ke seluruh Indonesia khusus Sabtu-Minggu ini 🚚💨 #GratisOngkir #Weekend',
                'status'            => 'scheduled',
                'platform_post_id'  => null,
                'published_at'      => null,
                'scheduled_at'      => now()->addDays(2)->setHour(9),
                'created_by'        => $staff2->id,
            ],
            [
                'social_account_id' => $fbAccount->id,
                'platform'          => 'facebook',
                'caption'           => 'Tips perawatan produk agar tetap awet dan berkualitas. Simak artikel lengkapnya di link bio!',
                'status'            => 'scheduled',
                'platform_post_id'  => null,
                'published_at'      => null,
                'scheduled_at'      => now()->addDays(5)->setHour(14),
                'created_by'        => $admin->id,
            ],
            // Draft
            [
                'social_account_id' => $igAccount->id,
                'platform'          => 'instagram',
                'caption'           => 'Konsep foto produk untuk kampanye bulan April — masih draft, perlu review tim kreatif.',
                'status'            => 'draft',
                'platform_post_id'  => null,
                'published_at'      => null,
                'scheduled_at'      => null,
                'created_by'        => $staff1->id,
            ],
            // Failed
            [
                'social_account_id' => $fbAccount->id,
                'platform'          => 'facebook',
                'caption'           => 'Post promosi yang gagal dipublish karena token expired.',
                'status'            => 'failed',
                'platform_post_id'  => null,
                'published_at'      => null,
                'scheduled_at'      => now()->subDays(1),
                'fail_reason'       => 'Access token has expired. Please reconnect your Facebook account.',
                'created_by'        => $staff2->id,
            ],
        ];

        $posts = [];
        foreach ($postsData as $data) {
            $post = Post::firstOrCreate(
                ['platform' => $data['platform'], 'caption' => $data['caption']],
                $data
            );
            $posts[] = $post;
        }

        // ── 5. POST MEDIA ──────────────────────────────────────────────────
        $publishedPosts = collect($posts)->where('status', 'published');
        foreach ($publishedPosts as $i => $post) {
            if (PostMedia::where('post_id', $post->id)->exists()) continue;
            PostMedia::create([
                'post_id'    => $post->id,
                'file_path'  => 'post-media/demo-image-' . ($i + 1) . '.jpg',
                'media_type' => 'image',
                'sort_order' => 1,
            ]);
        }

        // ── 6. COMMENTS ────────────────────────────────────────────────────
        $commentsData = [
            // Facebook comments
            [
                'post_id'              => $posts[0]->id,
                'social_account_id'    => $fbAccount->id,
                'platform_comment_id'  => 'fbcmt_001',
                'platform'             => 'facebook',
                'commenter_id'         => 'user_fb_001',
                'commenter_username'   => 'Ahmad Fauzi',
                'content'              => 'Wah promo keren banget! Bisa order online ga?',
                'like_count'           => 5,
                'is_replied'           => true,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(5)->subHours(3),
            ],
            [
                'post_id'              => $posts[0]->id,
                'social_account_id'    => $fbAccount->id,
                'platform_comment_id'  => 'fbcmt_002',
                'platform'             => 'facebook',
                'commenter_id'         => 'user_fb_002',
                'commenter_username'   => 'Dewi Permata',
                'content'              => 'Min, pengiriman ke Surabaya berapa hari ya?',
                'like_count'           => 2,
                'is_replied'           => false,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(5)->subHours(1),
            ],
            [
                'post_id'              => $posts[0]->id,
                'social_account_id'    => $fbAccount->id,
                'platform_comment_id'  => 'fbcmt_003',
                'platform'             => 'facebook',
                'commenter_id'         => 'user_fb_003',
                'commenter_username'   => 'Rizky Pratama',
                'content'              => 'Sudah order, semoga cepat sampai! 🙏',
                'like_count'           => 8,
                'is_replied'           => true,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(4)->subHours(6),
            ],
            [
                'post_id'              => $posts[1]->id,
                'social_account_id'    => $fbAccount->id,
                'platform_comment_id'  => 'fbcmt_004',
                'platform'             => 'facebook',
                'commenter_id'         => 'user_fb_004',
                'commenter_username'   => 'Nurul Hidayah',
                'content'              => 'Produk terbaik yang pernah saya beli! Recommended 👍',
                'like_count'           => 15,
                'is_replied'           => false,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(2)->subHours(4),
            ],
            [
                'post_id'              => $posts[1]->id,
                'social_account_id'    => $fbAccount->id,
                'platform_comment_id'  => 'fbcmt_005',
                'platform'             => 'facebook',
                'commenter_id'         => 'user_fb_005',
                'commenter_username'   => 'Spam Akun',
                'content'              => 'Klik link ini untuk dapat hadiah: bit.ly/xxxxx',
                'like_count'           => 0,
                'is_replied'           => false,
                'is_hidden'            => true,
                'commented_at'         => now()->subDays(2)->subHours(2),
            ],
            // Instagram comments
            [
                'post_id'              => $posts[2]->id,
                'social_account_id'    => $igAccount->id,
                'platform_comment_id'  => 'igcmt_001',
                'platform'             => 'instagram',
                'commenter_id'         => 'user_ig_001',
                'commenter_username'   => 'fashionista_id',
                'content'              => 'Keren banget kak! Ada size M ga? 😍',
                'like_count'           => 12,
                'is_replied'           => true,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(4)->subHours(5),
            ],
            [
                'post_id'              => $posts[2]->id,
                'social_account_id'    => $igAccount->id,
                'platform_comment_id'  => 'igcmt_002',
                'platform'             => 'instagram',
                'commenter_id'         => 'user_ig_002',
                'commenter_username'   => 'stylish_maya',
                'content'              => 'Harganya berapa kak? DM ya 🙏',
                'like_count'           => 3,
                'is_replied'           => false,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(4)->subHours(2),
            ],
            [
                'post_id'              => $posts[3]->id,
                'social_account_id'    => $igAccount->id,
                'platform_comment_id'  => 'igcmt_003',
                'platform'             => 'instagram',
                'commenter_id'         => 'user_ig_003',
                'commenter_username'   => 'kerajinan_lovers',
                'content'              => 'Wah proses pembuatannya keren! Berapa lama pengerjaannya?',
                'like_count'           => 7,
                'is_replied'           => false,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(1)->subHours(8),
            ],
            [
                'post_id'              => $posts[3]->id,
                'social_account_id'    => $igAccount->id,
                'platform_comment_id'  => 'igcmt_004',
                'platform'             => 'instagram',
                'commenter_id'         => 'user_ig_004',
                'commenter_username'   => 'handmade_enthusiast',
                'content'              => 'Support lokal! Bangga ada produk Indonesia berkualitas tinggi 🇮🇩',
                'like_count'           => 22,
                'is_replied'           => true,
                'is_hidden'            => false,
                'commented_at'         => now()->subDays(1)->subHours(3),
            ],
        ];

        $comments = [];
        foreach ($commentsData as $data) {
            $comment = Comment::firstOrCreate(
                ['platform_comment_id' => $data['platform_comment_id']],
                $data
            );
            $comments[] = $comment;
        }

        // ── 7. COMMENT REPLIES ─────────────────────────────────────────────
        $repliedComments = collect($comments)->where('is_replied', true)->where('is_hidden', false);
        foreach ($repliedComments as $comment) {
            if (CommentReply::where('comment_id', $comment->id)->exists()) continue;

            $replyTexts = [
                'fbcmt_001' => 'Halo kak Ahmad! Bisa order di website kami ya kak 😊',
                'fbcmt_003' => 'Terima kasih kak Rizky! Semoga cepat sampai 🙏',
                'igcmt_001' => 'Tersedia size M kak! Bisa langsung order di bio link ya 😊',
                'igcmt_004' => 'Terima kasih supportnya kak! Bangga bisa menghadirkan produk berkualitas 🙏🇮🇩',
            ];

            $replyText = $replyTexts[$comment->platform_comment_id]
                ?? 'Terima kasih atas komentarnya kak! Ada yang bisa kami bantu? 😊';

            CommentReply::create([
                'comment_id'        => $comment->id,
                'reply'             => $replyText,
                'replied_by'        => $staff1->id,
                'platform_reply_id' => 'reply_' . $comment->platform_comment_id,
                'is_sent'           => true,
                'sent_at'           => $comment->commented_at->addHours(1),
            ]);
        }

        // ── 8. MESSAGES ────────────────────────────────────────────────────
        $messagesData = [
            [
                'social_account_id'   => $fbAccount->id,
                'platform_message_id' => 'fbmsg_001',
                'platform'            => 'facebook',
                'sender_id'           => 'fb_sender_001',
                'sender_username'     => 'Hendra Wijaya',
                'message'             => 'Halo min, saya mau tanya stok produk X masih ada ga? Mau beli buat hadiah ulang tahun teman.',
                'status'              => 'resolved',
                'is_read'             => true,
                'sent_at'             => now()->subDays(4),
            ],
            [
                'social_account_id'   => $fbAccount->id,
                'platform_message_id' => 'fbmsg_002',
                'platform'            => 'facebook',
                'sender_id'           => 'fb_sender_002',
                'sender_username'     => 'Linda Kusuma',
                'message'             => 'Min mau tanya, untuk pengiriman ke luar Jawa pakai ekspedisi apa ya? Dan estimasi berapa hari?',
                'status'              => 'follow-up',
                'is_read'             => true,
                'sent_at'             => now()->subDays(2),
            ],
            [
                'social_account_id'   => $fbAccount->id,
                'platform_message_id' => 'fbmsg_003',
                'platform'            => 'facebook',
                'sender_id'           => 'fb_sender_003',
                'sender_username'     => 'Agus Firmansyah',
                'message'             => 'Apakah ada reseller? Saya tertarik untuk jadi reseller produk ini.',
                'status'              => 'new',
                'is_read'             => false,
                'sent_at'             => now()->subHours(5),
            ],
            [
                'social_account_id'   => $igAccount->id,
                'platform_message_id' => 'igmsg_001',
                'platform'            => 'instagram',
                'sender_id'           => 'ig_sender_001',
                'sender_username'     => 'cantik_selalu_99',
                'message'             => 'Kak mau tanya ini ada COD tidak ya? Lokasi saya di Bandung 🙏',
                'status'              => 'new',
                'is_read'             => false,
                'sent_at'             => now()->subHours(3),
            ],
            [
                'social_account_id'   => $igAccount->id,
                'platform_message_id' => 'igmsg_002',
                'platform'            => 'instagram',
                'sender_id'           => 'ig_sender_002',
                'sender_username'     => 'belanja_hemat_id',
                'message'             => 'Harga grosirnya berapa ya kak kalau beli 10 pcs?',
                'status'              => 'new',
                'is_read'             => false,
                'sent_at'             => now()->subHours(1),
            ],
            [
                'social_account_id'   => $igAccount->id,
                'platform_message_id' => 'igmsg_003',
                'platform'            => 'instagram',
                'sender_id'           => 'ig_sender_003',
                'sender_username'     => 'fashion_addict_id',
                'message'             => 'Barangnya sudah diterima kak! Kualitasnya mantap banget, pasti repeat order! ⭐⭐⭐⭐⭐',
                'status'              => 'resolved',
                'is_read'             => true,
                'sent_at'             => now()->subDays(1),
            ],
        ];

        $messages = [];
        foreach ($messagesData as $data) {
            $message = Message::firstOrCreate(
                ['platform_message_id' => $data['platform_message_id']],
                $data
            );
            $messages[] = $message;
        }

        // ── 9. MESSAGE REPLIES ─────────────────────────────────────────────
        $repliedMessages = collect($messages)->whereIn('status', ['resolved', 'follow-up']);
        foreach ($repliedMessages as $message) {
            if (MessageReply::where('message_id', $message->id)->exists()) continue;

            $replyMap = [
                'fbmsg_001' => 'Halo kak Hendra! Stok masih ada kak. Silakan order langsung di website kami ya 😊',
                'fbmsg_002' => 'Halo kak Linda! Kami menggunakan JNE, JNT, dan SiCepat. Estimasi 3-7 hari kerja untuk luar Jawa.',
                'igmsg_003' => 'Terima kasih review bintang 5-nya kak! Senang bisa membantu 🙏 Nantikan produk baru kami ya!',
            ];

            MessageReply::create([
                'message_id'        => $message->id,
                'reply'             => $replyMap[$message->platform_message_id] ?? 'Terima kasih pesannya kak! Akan kami tindak lanjuti segera 🙏',
                'replied_by'        => $staff1->id,
                'platform_reply_id' => 'mreply_' . $message->platform_message_id,
                'is_sent'           => true,
                'sent_at'           => $message->sent_at->addMinutes(30),
            ]);
        }

        // ── 10. METRICS ────────────────────────────────────────────────────
        $publishedPostsList = collect($posts)->where('status', 'published')->values();
        $accounts           = [$fbAccount, $igAccount];

        // Generate metrics untuk 14 hari terakhir
        for ($day = 13; $day >= 0; $day--) {
            $date = now()->subDays($day)->toDateString();

            // Account-level metrics
            foreach ($accounts as $account) {
                $isFb   = $account->platform === 'facebook';
                $factor = mt_rand(80, 120) / 100;

                Metric::firstOrCreate(
                    ['social_account_id' => $account->id, 'post_id' => null, 'recorded_date' => $date],
                    [
                        'platform'    => $account->platform,
                        'likes'       => (int) (($isFb ? mt_rand(40, 120) : mt_rand(80, 250)) * $factor),
                        'comments'    => (int) (($isFb ? mt_rand(10, 40) : mt_rand(15, 60)) * $factor),
                        'shares'      => (int) (($isFb ? mt_rand(5, 25) : mt_rand(2, 15)) * $factor),
                        'reach'       => (int) (($isFb ? mt_rand(500, 2000) : mt_rand(800, 3500)) * $factor),
                        'impressions' => (int) (($isFb ? mt_rand(800, 3000) : mt_rand(1200, 5000)) * $factor),
                        'saves'       => (int) (($isFb ? mt_rand(2, 15) : mt_rand(20, 80)) * $factor),
                        'clicks'      => (int) (($isFb ? mt_rand(15, 60) : mt_rand(10, 40)) * $factor),
                    ]
                );
            }

            // Post-level metrics for published posts
            foreach ($publishedPostsList as $post) {
                $publishedDaysAgo = now()->diffInDays($post->published_at);
                if ($day > $publishedDaysAgo) continue;

                $isFb   = $post->platform === 'facebook';
                $factor = max(0.1, 1 - ($day * 0.05)) * (mt_rand(85, 115) / 100);

                Metric::firstOrCreate(
                    ['social_account_id' => $post->social_account_id, 'post_id' => $post->id, 'recorded_date' => $date],
                    [
                        'platform'    => $post->platform,
                        'likes'       => (int) (($isFb ? mt_rand(20, 80) : mt_rand(50, 200)) * $factor),
                        'comments'    => (int) (($isFb ? mt_rand(3, 20) : mt_rand(5, 40)) * $factor),
                        'shares'      => (int) (($isFb ? mt_rand(2, 15) : mt_rand(1, 10)) * $factor),
                        'reach'       => (int) (($isFb ? mt_rand(200, 800) : mt_rand(400, 1500)) * $factor),
                        'impressions' => (int) (($isFb ? mt_rand(300, 1200) : mt_rand(600, 2500)) * $factor),
                        'saves'       => (int) (($isFb ? mt_rand(1, 8) : mt_rand(10, 50)) * $factor),
                        'clicks'      => (int) (($isFb ? mt_rand(5, 30) : mt_rand(3, 20)) * $factor),
                    ]
                );
            }
        }

        // ── 11. CUSTOM NOTIFICATIONS ───────────────────────────────────────
        $notificationsData = [
            [
                'user_id'    => $admin->id,
                'title'      => 'Post Berhasil Dipublish! ✅',
                'message'    => 'Post "Promo spesial akhir bulan!" berhasil dipublish ke Facebook.',
                'type'       => 'success',
                'action_url' => '/admin/posts',
                'is_read'    => true,
                'read_at'    => now()->subDays(5),
            ],
            [
                'user_id'    => $admin->id,
                'title'      => 'Token Akan Expired ⚠️',
                'message'    => 'Token akun @toko_maju_jaya (Instagram) akan expired dalam 7 hari.',
                'type'       => 'warning',
                'action_url' => '/admin/social-accounts',
                'is_read'    => false,
                'read_at'    => null,
            ],
            [
                'user_id'    => $admin->id,
                'title'      => 'Pesan Baru Masuk 💬',
                'message'    => 'Ada 3 pesan baru yang belum dibalas dari Facebook dan Instagram.',
                'type'       => 'message',
                'action_url' => '/admin/messages',
                'is_read'    => false,
                'read_at'    => null,
            ],
            [
                'user_id'    => $staff1->id,
                'title'      => 'Post Berhasil Dijadwalkan 📅',
                'message'    => 'Post "Flash Sale besok" berhasil dijadwalkan untuk besok pukul 10.00 WIB.',
                'type'       => 'schedule',
                'action_url' => '/admin/posts',
                'is_read'    => true,
                'read_at'    => now()->subHours(2),
            ],
            [
                'user_id'    => $staff1->id,
                'title'      => 'Komentar Baru 💭',
                'message'    => 'Ada 5 komentar baru yang belum dibalas pada post Instagram.',
                'type'       => 'comment',
                'action_url' => '/admin/comments',
                'is_read'    => false,
                'read_at'    => null,
            ],
            [
                'user_id'    => $staff2->id,
                'title'      => 'Post Gagal Dipublish ❌',
                'message'    => 'Post "Promosi yang gagal" gagal: Access token has expired.',
                'type'       => 'error',
                'action_url' => '/admin/posts',
                'is_read'    => false,
                'read_at'    => null,
            ],
        ];

        foreach ($notificationsData as $data) {
            CustomNotification::firstOrCreate(
                ['user_id' => $data['user_id'], 'title' => $data['title']],
                $data
            );
        }

        $this->command->info('✅ Demo data berhasil dibuat:');
        $this->command->info('   👤 2 staff users (staff1@example.com, staff2@example.com) — password: password');
        $this->command->info('   📱 2 social accounts (Facebook + Instagram)');
        $this->command->info('   📝 ' . count($postsData) . ' posts (published, scheduled, draft, failed)');
        $this->command->info('   💬 ' . count($commentsData) . ' comments + replies');
        $this->command->info('   📩 ' . count($messagesData) . ' messages + replies');
        $this->command->info('   📊 Metrics untuk 14 hari terakhir');
        $this->command->info('   🔔 ' . count($notificationsData) . ' notifications');
    }
}
