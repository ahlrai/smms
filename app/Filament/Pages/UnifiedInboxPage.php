<?php

namespace App\Filament\Pages;

use App\Models\Message;
use App\Models\MessageReply;
use App\Services\InstagramService;
use App\Services\FacebookService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class UnifiedInboxPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static string|\UnitEnum|null $navigationGroup = 'Social Media';

    protected static ?string $navigationLabel = 'Pesan Masuk';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.unified-inbox';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('message.view') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */

    public ?int $selectedId = null;

    public string $filter = 'all';

    public string $replyText = '';

    /*
    |--------------------------------------------------------------------------
    | NAVIGATION BADGE
    |--------------------------------------------------------------------------
    */

    public static function getNavigationBadge(): ?string
    {
        $count = Message::where('is_read', false)->count();

        return $count > 0
            ? (string) $count
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /*
    |--------------------------------------------------------------------------
    | MESSAGE LIST
    |--------------------------------------------------------------------------
    */

    public function getMessages(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Message::with([
            'socialAccount',
            'replies.replier',
        ])->latest('sent_at');

        match ($this->filter) {

            'unread' =>
                $query->where('is_read', false),

            'facebook' =>
                $query->where('platform', 'facebook'),

            'instagram' =>
                $query->where('platform', 'instagram'),

            default => null,
        };

        return $query->get();
    }

    public function getCounts(): array
    {
        return [

            'all' =>
                Message::count(),

            'unread' =>
                Message::where('is_read', false)->count(),

            'facebook' =>
                Message::where('platform', 'facebook')->count(),

            'instagram' =>
                Message::where('platform', 'instagram')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | SELECTED MESSAGE
    |--------------------------------------------------------------------------
    */

    public function getSelected(): ?Message
    {
        if (!$this->selectedId) {
            return null;
        }

        return Message::with([
            'socialAccount',
            'replies.replier',
        ])->find($this->selectedId);
    }

    public function selectMessage(int $id): void
    {
        $this->selectedId = $id;

        $this->replyText = '';

        Message::find($id)?->update([
            'is_read' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER
    |--------------------------------------------------------------------------
    */

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;

        $this->selectedId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | SEND REPLY
    |--------------------------------------------------------------------------
    */

    public function sendReply(
    InstagramService $ig,
    FacebookService $fb
): void{
    abort_unless(auth()->user()?->hasPermissionTo('message.reply'), 403);

    $reply = trim($this->replyText);

    if (
        empty($reply)
        || !$this->selectedId
    ) {

        Notification::make()
            ->title('Balasan tidak boleh kosong.')
            ->warning()
            ->send();

        return;
    }

    $message = Message::with(
        'socialAccount'
    )->findOrFail(
        $this->selectedId
    );

    if (config('app.debug')) {
    \Log::info('PLATFORM', [
    'platform' => $message->platform,
]);
    }

    /*
    |--------------------------------------------------------------------------
    | INSTAGRAM
    |--------------------------------------------------------------------------
    */

    $account = $message->socialAccount;

/*
|--------------------------------------------------------------------------
| FACEBOOK
|--------------------------------------------------------------------------
*/

if ($message->platform === 'facebook') {

    $fb = app(\App\Services\FacebookService::class);

    $result = $fb->sendMessage(
        recipientId: $message->sender_id,
        text: $reply,
        pageToken: $account->access_token
    );

    if (config('app.debug')) {
    \Log::info('FB SEND RESULT', $result);
    }

    if (isset($result['error'])) {

        MessageReply::create([
            'message_id' => $message->id,
            'reply' => $reply,
            'replied_by' => auth()->id(),
            'is_sent' => false,
        ]);

        Notification::make()
            ->title(
                $result['error']['message']
                ?? 'Gagal mengirim pesan Facebook'
            )
            ->danger()
            ->send();

        return;
    }

    MessageReply::create([
        'message_id' => $message->id,
        'reply' => $reply,
        'replied_by' => auth()->id(),
        'platform_reply_id' =>
            $result['message_id']
            ?? $result['recipient_id']
            ?? null,
        'is_sent' => true,
        'sent_at' => now(),
    ]);
}

/*
|--------------------------------------------------------------------------
| INSTAGRAM
|--------------------------------------------------------------------------
*/

elseif ($message->platform === 'instagram') {

    $result = $ig->sendMessage(
        recipientId: $message->sender_id,
        message: $reply,
        token: $account->access_token
    );
    if (config('app.debug')) {
    \Log::info('IG SEND RESULT', $result);
    }

    if (isset($result['error'])) {

        MessageReply::create([
            'message_id' => $message->id,
            'reply' => $reply,
            'replied_by' => auth()->id(),
            'is_sent' => false,
        ]);

        Notification::make()
            ->title(
                $result['error']['message']
                ?? 'Gagal mengirim DM Instagram'
            )
            ->danger()
            ->send();

        return;
    }

    MessageReply::create([
        'message_id' => $message->id,
        'reply' => $reply,
        'replied_by' => auth()->id(),
        'platform_reply_id' =>
            $result['message_id']
            ?? $result['recipient_id']
            ?? null,
        'is_sent' => true,
        'sent_at' => now(),
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS
    |--------------------------------------------------------------------------
    */

    $message->update([
        'status' => 'follow-up',
        'is_read' => true,
    ]);

    $this->replyText = '';

    Notification::make()
        ->title('Balasan berhasil dikirim.')
        ->success()
        ->send();
}

    /*
    |--------------------------------------------------------------------------
    | RESOLVED
    |--------------------------------------------------------------------------
    */

    public function markResolved(int $id): void
    {
        Message::findOrFail($id)->update([
            'status' => 'resolved',
        ]);

        Notification::make()
            ->title('Ditandai selesai.')
            ->success()
            ->send();
    }
}