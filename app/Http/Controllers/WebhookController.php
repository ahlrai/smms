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
    | HANDLE WEBHOOK
    |--------------------------------------------------------------------------
    */

    public function handle(Request $request)
    {
        Log::info('WEBHOOK', $request->all());

        if ($request->input('object') === 'instagram') {
            return $this->handleInstagram($request);
        }

        if ($request->input('object') === 'page') {
            return $this->handleFacebook($request);
        }

        return response('OK', 200);
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE INSTAGRAM
    |--------------------------------------------------------------------------
    */

    private function handleInstagram(Request $request)
    {
        foreach ($request->input('entry', []) as $entry) {

            foreach ($entry['messaging'] ?? [] as $msg) {

                if (($msg['message']['is_echo'] ?? false) === true) {

                    Log::info('SKIP ECHO MESSAGE');

                    continue;
                }

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
                | Cari akun Instagram tujuan
                |--------------------------------------------------------------------------
                */

                $account = SocialAccount::where(
                    'platform',
                    'instagram'
                )
                ->where(
                    'account_id',
                    $recipientId
                )
                ->first();

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

                $username =
                    $this->instagram->getUsername(
                        $senderId,
                        $account->access_token
                    );

                if (!$username) {
                    $username = $senderId;
                }

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

        return response('OK', 200);
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE FACEBOOK
    |--------------------------------------------------------------------------
    */

    private function handleFacebook(Request $request)
    {
        foreach ($request->input('entry', []) as $entry) {

            foreach ($entry['messaging'] ?? [] as $msg) {

                if (($msg['message']['is_echo'] ?? false) === true) {
                    continue;
                }

                $senderId =
                    $msg['sender']['id']
                    ?? null;

                $recipientId =
                    $msg['recipient']['id']
                    ?? null;

                $text =
                    $msg['message']['text']
                    ?? null;

                $mid =
                    $msg['message']['mid']
                    ?? uniqid('fb_');

                if (
                    !$senderId ||
                    !$recipientId ||
                    !$text
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Cari akun Facebook Page tujuan
                |--------------------------------------------------------------------------
                */

                $account = SocialAccount::where(
                    'platform',
                    'facebook'
                )
                ->where(
                    'account_id',
                    $recipientId
                )
                ->first();

                if (!$account) {

                    Log::warning(
                        'FB Account tidak ditemukan',
                        [
                            'recipient' => $recipientId,
                        ]
                    );

                    continue;
                }

                Message::firstOrCreate(

                    [
                        'platform_message_id' => $mid,
                    ],

                    [

                        'social_account_id' =>
                            $account->id,

                        'sender_id' =>
                            $senderId,

                        'sender_username' =>
                            $senderId,

                        'sender_avatar' =>
                            null,

                        'platform' =>
                            'facebook',

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
            }
        }

        return response('OK', 200);
    }
}