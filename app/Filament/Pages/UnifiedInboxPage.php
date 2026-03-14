<?php

namespace App\Filament\Pages;

use App\Models\Message;
use App\Models\MessageReply;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class UnifiedInboxPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';
    protected static string|\UnitEnum|null $navigationGroup  = 'Social Media';
    protected static ?string $navigationLabel                = 'Pesan Masuk';
    protected static ?int    $navigationSort                 = 2;
    protected string $view = 'filament.pages.unified-inbox';

    // State
    public ?int    $selectedId  = null;
    public string  $filter      = 'all';  // all | unread | facebook | instagram
    public string  $replyText   = '';

    public static function getNavigationBadge(): ?string
    {
        $count = Message::where('is_read', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function getMessages(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Message::with(['socialAccount', 'replies.replier'])
            ->latest('sent_at');

        match ($this->filter) {
            'unread'    => $query->where('is_read', false),
            'facebook'  => $query->where('platform', 'facebook'),
            'instagram' => $query->where('platform', 'instagram'),
            default     => null,
        };

        return $query->get();
    }

    public function getCounts(): array
    {
        return [
            'all'       => Message::count(),
            'unread'    => Message::where('is_read', false)->count(),
            'facebook'  => Message::where('platform', 'facebook')->count(),
            'instagram' => Message::where('platform', 'instagram')->count(),
        ];
    }

    public function getSelected(): ?Message
    {
        if (!$this->selectedId) return null;
        return Message::with(['replies.replier', 'socialAccount'])->find($this->selectedId);
    }

    public function selectMessage(int $id): void
    {
        $this->selectedId = $id;
        $this->replyText  = '';

        // Auto mark as read
        Message::find($id)?->update(['is_read' => true]);
    }

    public function setFilter(string $filter): void
    {
        $this->filter     = $filter;
        $this->selectedId = null;
    }

    public function sendReply(): void
    {
        $reply = trim($this->replyText);

        if (empty($reply) || !$this->selectedId) {
            Notification::make()->title('Balasan tidak boleh kosong.')->warning()->send();
            return;
        }

        $message = Message::findOrFail($this->selectedId);

        MessageReply::create([
            'message_id'  => $message->id,
            'reply'       => $reply,
            'replied_by'  => auth()->id(),
            'is_sent'     => false,
        ]);

        $message->update(['status' => 'follow-up', 'is_read' => true]);

        $this->replyText = '';

        Notification::make()
            ->title('Balasan terkirim!')
            ->success()
            ->send();
    }

    public function markResolved(int $id): void
    {
        Message::findOrFail($id)->update(['status' => 'resolved']);

        Notification::make()->title('Ditandai selesai.')->success()->send();
    }
}
