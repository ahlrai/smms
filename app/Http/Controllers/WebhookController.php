<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\SocialAccount;
use App\Services\InstagramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected InstagramService $instagram;

    public function __construct(
        InstagramService $instagram
    ) {
        $this->instagram = $instagram;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY WEBHOOK
    |--------------------------------------------------------------------------
    */

    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if (
            $mode === 'subscribe' &&
            $token === env('META_VERIFY_TOKEN')
        ) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE INSTAGRAM WEBHOOK
    |--------------------------------------------------------------------------
    */

    public function handle(Request $request)
    {
        Log::info(
            'WEBHOOK IG HIT',
            $request->all()
        );

        foreach ($request->input('entry', []) as $entry) {

            foreach ($entry['messaging'] ?? [] as $msg) {

                /*
                |--------------------------------------------------------------------------
                | SKIP ECHO MESSAGE
                |--------------------------------------------------------------------------
                */

                if (($msg['message']['is_echo'] ?? false) === true) {

                    Log::info('SKIP ECHO MESSAGE');

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | AMBIL DATA PESAN
                |--------------------------------------------------------------------------
                */

                $senderId =
                    $msg['sender']['id']
                    ?? null;

                $recipientId =
                    $msg['recipient']['id']
                    ?? null;

                $text =
                    $msg['message']['text']
                    ?? null;

                $platformMessageId =
                    $msg['message']['mid']
                    ?? uniqid('ig_');

                /*
                |--------------------------------------------------------------------------
                | SKIP SELF MESSAGE
                |--------------------------------------------------------------------------
                */

                if (
                    $senderId &&
                    $recipientId &&
                    $senderId === $recipientId
                ) {

                    Log::info(
                        'SKIP SELF MESSAGE',
                        [
                            'sender_id' => $senderId,
                        ]
                    );

                    continue;
                }

                if (
                    !$senderId ||
                    !$recipientId ||
                    !$text
                ) {

                    Log::warning(
                        'Webhook IG: data pesan tidak lengkap',
                        $msg
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CARI AKUN INSTAGRAM TUJUAN
                |--------------------------------------------------------------------------
                */

                $account = SocialAccount::where(
                    'account_id',
                    $recipientId
                )->first();

                if (
                    $account &&
                    $senderId === $account->account_id
                ) {

                    Log::info(
                        'SKIP MESSAGE FROM OWN ACCOUNT',
                        [
                            'sender_id' => $senderId,
                        ]
                    );

                    continue;
                }

                if (!$account) {

                    Log::warning(
                        'Social account tidak ditemukan',
                        [
                            'recipient_id' => $recipientId,
                        ]
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | AMBIL USERNAME INSTAGRAM
                |--------------------------------------------------------------------------
                */

                $username =
                    $this->instagram->getUsername(
                        $senderId,
                        $account->access_token
                    );

                if (!$username) {

                    $username =
                        $senderId;
                }

                /*
                |--------------------------------------------------------------------------
                | SIMPAN PESAN (ANTI DUPLIKAT)
                |--------------------------------------------------------------------------
                */

                $message = Message::firstOrCreate(

                    [
                        'platform_message_id' =>
                            $platformMessageId,
                    ],

                    [
                        'social_account_id' =>
                            $account->id,

                        'sender_id' =>
                            $senderId,

                        'sender_username' =>
                            $username,

                        'sender_avatar' =>
                            null,

                        'platform' =>
                            'instagram',

                        'message' =>
                            $text,

                        'status' =>
                            'new',

                        'is_read' =>
                            false,

                        'sent_at' =>
                            now(),
                    ]
                );

                Log::info(
                    'DM Instagram tersimpan',
                    [
                        'message_id' => $message->id,
                        'platform_message_id' => $platformMessageId,
                        'sender_id' => $senderId,
                        'sender_username' => $username,
                        'message' => $text,
                    ]
                );
            }
        }

        return response(
            'EVENT_RECEIVED',
            200
        );
    }
}