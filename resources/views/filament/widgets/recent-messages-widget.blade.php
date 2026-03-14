<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Pesan Terbaru</x-slot>
        <x-slot name="headerEnd">
            <a href="/admin/messages" style="font-size:12px;color:#14c8aa;text-decoration:none;">Lihat semua →</a>
        </x-slot>

        <style>
            .msg-list { display: flex; flex-direction: column; gap: 12px; }
            .msg-item { display: flex; align-items: flex-start; gap: 10px; }
            .msg-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: #fff; flex-shrink: 0; }
            .msg-avatar-fb { background: #1877F2; }
            .msg-avatar-ig { background: linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }
            .msg-body { flex: 1; min-width: 0; }
            .msg-name { font-size: 13px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 6px; }
            .dark .msg-name { color: #f9fafb; }
            .msg-text { font-size: 12px; color: #6b7280; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .msg-time { font-size: 11px; color: #9ca3af; margin-top: 2px; }
            .msg-badge { font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 10px; }
            .msg-badge-new { background: #fee2e2; color: #dc2626; }
            .msg-badge-fu  { background: #fef3c7; color: #d97706; }
            .msg-badge-done{ background: #d1fae5; color: #065f46; }
            .msg-unread { width: 7px; height: 7px; background: #ef4444; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
        </style>

        <div class="msg-list">
            @forelse ($this->getMessages() as $msg)
                <div class="msg-item">
                    <div class="msg-avatar {{ $msg->platform === 'facebook' ? 'msg-avatar-fb' : 'msg-avatar-ig' }}">
                        {{ strtoupper(substr($msg->sender_username, 0, 1)) }}
                    </div>
                    <div class="msg-body">
                        <div class="msg-name">
                            {{ $msg->sender_username }}
                            @if(!$msg->is_read)
                                <span class="msg-unread"></span>
                            @endif
                            <span class="msg-badge {{ match($msg->status) { 'new' => 'msg-badge-new', 'follow-up' => 'msg-badge-fu', default => 'msg-badge-done' } }}">
                                {{ match($msg->status) { 'new' => 'Baru', 'follow-up' => 'Follow-up', default => 'Selesai' } }}
                            </span>
                        </div>
                        <div class="msg-text">{{ $msg->message }}</div>
                        <div class="msg-time">{{ $msg->sent_at?->diffForHumans() }} · {{ ucfirst($msg->platform) }}</div>
                    </div>
                </div>
            @empty
                <p style="font-size:13px;color:#9ca3af;text-align:center;padding:16px 0;">Belum ada pesan masuk.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
