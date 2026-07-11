<?php

namespace App\Jobs;

use App\Models\CustomNotification;
use App\Models\Message;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function handle(FacebookService $fb, InstagramService $ig): void
    {
        $accounts = SocialAccount::all();

        foreach ($accounts as $account) {
            // Skip akun dengan token expired
            if ($account->isTokenExpired()) {
                Log::warning('SyncMessagesJob: Token expired untuk ' . $account->username);
                continue;
            }

            try {
                if ($account->platform === 'facebook') {
                    $this->syncFacebookMessages($account, $fb);
                } else {
                    $this->syncInstagramMessages($account, $ig);
                }
            } catch (\Exception $e) {
                Log::error('SyncMessagesJob error (' . $account->username . '): ' . $e->getMessage());
            }
        }
    }

    private function syncFacebookMessages(SocialAccount $account, FacebookService $fb): void
    {
        $conversations = $fb->fetchConversations($account);
        $newCount      = 0;

        foreach ($conversations as $conv) {
            // Ambil pesan dalam conversation
            $messages = collect($conv['messages']['data'] ?? [])
    ->sortBy('created_time')
    ->values();

foreach ($messages as $msg) {
                // Skip pesan dari halaman kita sendiri
                if (isset($msg['from']['id']) && $msg['from']['id'] === $account->account_id) {
                    continue;
                }

                // De-duplikasi — skip jika sudah ada
                $exists = Message::where('platform_message_id', $msg['id'])->exists();
                if ($exists) continue;

                Log::info('FB MESSAGE TIME', [
    'message' => $msg['message'] ?? '',
    'created_time_api' => $msg['created_time'] ?? null,
]);

                $message = Message::create([
                    'social_account_id'   => $account->id,
                    'platform_message_id' => $msg['id'],
                    'sender_id'           => $msg['from']['id'] ?? null,
                    'sender_username'     => $msg['from']['name'] ?? 'Unknown',
                    'platform'            => 'facebook',
                    'message'             => $msg['message'] ?? '',
                    'status'              => 'new',
                    'is_read'             => false,
                    'sent_at' => isset($msg['created_time'])
    ? Carbon::parse($msg['created_time'])->setTimezone(config('app.timezone'))
    : now(),
                ]);

                $newCount++;
            }
        }

        if ($newCount > 0) {

    Log::info(
        'SyncMessagesJob: ' .
        $newCount .
        ' pesan Facebook baru dari ' .
        $account->username
    );
}
    }

    private function syncInstagramMessages(SocialAccount $account, InstagramService $ig): void
    {
        $conversations = $ig->fetchMessages($account);
        $newCount      = 0;

        foreach ($conversations as $conv) {
            $messages = collect($conv['messages']['data'] ?? [])
    ->sortBy('created_time')
    ->values();

foreach ($messages as $msg) {
                if (isset($msg['from']['id']) && $msg['from']['id'] === $account->account_id) {
                    continue;
                }

                $exists = Message::where('platform_message_id', $msg['id'])->exists();
                if ($exists) continue;

                Message::create([
                    'social_account_id'   => $account->id,
                    'platform_message_id' => $msg['id'],
                    'sender_id'           => $msg['from']['id'] ?? null,
                    'sender_username'     => $msg['from']['username'] ?? 'Unknown',
                    'platform'            => 'instagram',
                    'message'             => $msg['text'] ?? '',
                    'status'              => 'new',
                    'is_read'             => false,
                    'sent_at' => isset($msg['created_time'])
                    ? Carbon::parse($msg['created_time'])->setTimezone(config('app.timezone'))
                    : now(),
                ]);

                $newCount++;
            }
        }

       if ($newCount > 0) {

    Log::info(
        'SyncMessagesJob: ' .
        $newCount .
        ' DM Instagram baru dari ' .
        $account->username
    );
}
    }
}