<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Pesan Terbaru</x-slot>
        <x-slot name="headerEnd">
            <a href="/admin/messages" style="font-size:12px; color:#14c8aa; text-decoration:none;">Lihat semua →</a>
        </x-slot>

        <style>
            .mw-card {
                border-radius: 12px;
                padding: 14px 16px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                border: 1px solid rgba(255,255,255,.07);
                background: rgba(255,255,255,.03);
            }
            .mw-avatar {
                width: 40px; height: 40px;
                border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-size: 14px; font-weight: 700; color: white;
                flex-shrink: 0;
            }
            .mw-avatar-fb { background: linear-gradient(135deg,#1877F2,#0d5fd1); }
            .mw-avatar-ig {
                background: radial-gradient(circle at 30% 110%,
                    #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            }
            .mw-name  { font-size: 13px; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
            .mw-text  { font-size: 12px; color: #64748b; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .mw-time  { font-size: 11px; color: #475569; margin-top: 3px; }
            .mw-dot   { width: 7px; height: 7px; background: #ef4444; border-radius: 50%; flex-shrink: 0; }
            .mw-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; }
            .mw-new   { background: rgba(248,113,113,.12); color: #f87171; }
            .mw-fu    { background: rgba(251,191,36,.12);  color: #fbbf24; }
            .mw-done  { background: rgba(74,222,128,.12);  color: #4ade80; }
        </style>

        <div style="display:flex; flex-direction:column; gap:8px;">
            @forelse ($this->getMessages() as $msg)
                <div class="mw-card">
                    <div class="mw-avatar {{ $msg->platform === 'facebook' ? 'mw-avatar-fb' : 'mw-avatar-ig' }}">
                        {{ strtoupper(substr($msg->sender_username, 0, 1)) }}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="mw-name">
                            {{ $msg->sender_username }}
                            @if(!$msg->is_read)
                                <span class="mw-dot"></span>
                            @endif
                            @php
                                $badgeClass = match($msg->status) {
                                    'new'       => 'mw-new',
                                    'follow-up' => 'mw-fu',
                                    default     => 'mw-done',
                                };
                                $badgeLabel = match($msg->status) {
                                    'new'       => 'Baru',
                                    'follow-up' => 'Follow-up',
                                    default     => 'Selesai',
                                };
                            @endphp
                            <span class="mw-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                        </div>
                        <div class="mw-text">{{ $msg->message }}</div>
                        <div class="mw-time">{{ $msg->sent_at?->diffForHumans() }} · {{ ucfirst($msg->platform) }}</div>
                    </div>
                </div>
            @empty
                <p style="font-size:13px; color:#475569; text-align:center; padding:20px 0;">Belum ada pesan masuk.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
