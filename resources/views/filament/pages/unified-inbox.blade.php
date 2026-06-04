<x-filament-panels::page>
    @php
        $messages  = $this->getMessages();
        $counts    = $this->getCounts();
        $selected  = $this->getSelected();
        $filters   = [
            'all'       => ['label' => 'Semua',       'count' => $counts['all']],
            'unread'    => ['label' => 'Belum Dibaca', 'count' => $counts['unread']],
            'facebook'  => ['label' => 'Facebook',     'count' => $counts['facebook']],
            'instagram' => ['label' => 'Instagram',    'count' => $counts['instagram']],
        ];
    @endphp

    <style>
        /* ── Layout ── */
        .inbox-wrap { display:flex; height:calc(100vh - 160px); min-height:500px; border:1px solid rgba(255,255,255,.07); border-radius:16px; overflow:hidden; }

        /* ── Left Panel ── */
        .inbox-left { width:340px; flex-shrink:0; display:flex; flex-direction:column; border-right:1px solid rgba(255,255,255,.07); }
        .inbox-header { padding:16px 16px 12px; border-bottom:1px solid rgba(255,255,255,.07); }
        .inbox-title { font-size:15px; font-weight:700; color:#f1f5f9; margin-bottom:2px; }
        .inbox-subtitle { font-size:12px; color:#64748b; }
        .inbox-tabs { display:flex; gap:6px; padding:10px 14px; flex-wrap:wrap; border-bottom:1px solid rgba(255,255,255,.06); }
        .inbox-tab { font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; border:1px solid rgba(255,255,255,.1); background:transparent; cursor:pointer; color:#64748b; display:flex; align-items:center; gap:5px; transition:all .15s; }
        .inbox-tab:hover { background:rgba(255,255,255,.05); color:#94a3b8; }
        .inbox-tab.active { background:rgba(255,255,255,.1); color:#f1f5f9; border-color:rgba(255,255,255,.2); }
        .inbox-tab .cnt { background:rgba(255,255,255,.12); border-radius:10px; padding:0 6px; font-size:10px; }
        .inbox-tab.active .cnt { background:rgba(20,200,170,.2); color:#14c8aa; }
        .inbox-list { flex:1; overflow-y:auto; }
        .inbox-item { padding:14px 16px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,.04); transition:background .12s; display:flex; gap:12px; align-items:flex-start; }
        .inbox-item:hover { background:rgba(255,255,255,.04); }
        .inbox-item.active { background:rgba(20,200,170,.07); border-left:3px solid #14c8aa; padding-left:13px; }
        .inbox-item.unread { background:rgba(255,255,255,.02); }
        .inbox-avatar { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:#fff; flex-shrink:0; position:relative; }
        .inbox-avatar-fb { background:linear-gradient(135deg,#1877F2,#0d5fd1); }
        .inbox-avatar-ig { background:radial-gradient(circle at 30% 110%,#f09433,#dc2743,#bc1888); }
        .unread-dot { position:absolute; top:0; right:0; width:9px; height:9px; background:#ef4444; border-radius:50%; border:2px solid #0f172a; }
        .inbox-meta { flex:1; min-width:0; }
        .inbox-name { font-size:13px; font-weight:600; color:#e2e8f0; display:flex; align-items:center; justify-content:space-between; }
        .inbox-time { font-size:11px; color:#475569; font-weight:400; }
        .inbox-badges { display:flex; gap:5px; margin:4px 0; }
        .badge { font-size:10px; font-weight:600; padding:1px 7px; border-radius:10px; }
        .badge-fb { background:rgba(24,119,242,.18); color:#60a5fa; }
        .badge-ig { background:rgba(220,39,67,.18); color:#f9a8d4; }
        .badge-new { background:rgba(20,200,170,.15); color:#14c8aa; }
        .badge-fu  { background:rgba(245,158,11,.15); color:#fbbf24; }
        .badge-done{ background:rgba(100,116,139,.15); color:#94a3b8; }
        .inbox-preview { font-size:12px; color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

        /* ── Right Panel ── */
        .inbox-right { flex:1; display:flex; flex-direction:column; }
        .inbox-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#334155; gap:12px; }
        .inbox-empty-icon { font-size:48px; }

        /* Conversation header */
        .conv-header { padding:14px 20px; border-bottom:1px solid rgba(255,255,255,.07); display:flex; align-items:center; justify-content:space-between; }
        .conv-user { display:flex; align-items:center; gap:10px; }
        .conv-name { font-size:14px; font-weight:700; color:#f1f5f9; }
        .conv-sub { font-size:11px; color:#475569; }
        .conv-actions { display:flex; gap:6px; }
        .conv-btn { font-size:11px; font-weight:500; padding:5px 12px; border-radius:7px; border:1px solid rgba(255,255,255,.1); background:transparent; color:#94a3b8; cursor:pointer; transition:all .15s; }
        .conv-btn:hover { background:rgba(255,255,255,.06); color:#f1f5f9; }
        .conv-btn-done { border-color:rgba(20,200,170,.3); color:#14c8aa; }
        .conv-btn-done:hover { background:rgba(20,200,170,.08); }

        /* Messages thread */
        .conv-thread { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:12px; }
        .msg-bubble { max-width:75%; }
        .msg-bubble-incoming { align-self:flex-start; }
        .msg-bubble-outgoing { align-self:flex-end; }
        .msg-bubble-incoming .bubble { background:rgba(255,255,255,.06); border-radius:4px 14px 14px 14px; padding:10px 14px; font-size:13px; color:#e2e8f0; line-height:1.5; }
        .msg-bubble-outgoing .bubble { background:rgba(20,200,170,.15); border-radius:14px 4px 14px 14px; padding:10px 14px; font-size:13px; color:#e2e8f0; line-height:1.5; border:1px solid rgba(20,200,170,.2); }
        .msg-meta { font-size:10px; color:#475569; margin-top:4px; }
        .msg-bubble-incoming .msg-meta { text-align:left; }
        .msg-bubble-outgoing .msg-meta { text-align:right; }

        /* Reply box */
        .conv-reply { padding:12px 20px; border-top:1px solid rgba(255,255,255,.07); display:flex; gap:10px; align-items:flex-end; }
        .conv-reply textarea { flex:1; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); border-radius:10px; padding:10px 14px; color:#e2e8f0; font-size:13px; resize:none; outline:none; font-family:inherit; line-height:1.5; min-height:44px; max-height:120px; transition:border-color .15s; }
        .conv-reply textarea:focus { border-color:rgba(20,200,170,.4); }
        .conv-reply textarea::placeholder { color:#475569; }
        .send-btn { background:#14c8aa; color:#fff; border:none; border-radius:9px; padding:10px 18px; font-size:13px; font-weight:600; cursor:pointer; flex-shrink:0; transition:background .15s; }
        .send-btn:hover { background:#0fa88e; }
        .send-btn:disabled { opacity:.5; cursor:not-allowed; }
    </style>

    <div class="inbox-wrap">
        {{-- ── LEFT PANEL ── --}}
        <div class="inbox-left">
            <div class="inbox-header">
                <div class="inbox-title">💬 Unified Inbox</div>
                <div class="inbox-subtitle">{{ $counts['unread'] }} pesan belum dibalas</div>
            </div>

            {{-- Filter tabs --}}
            <div class="inbox-tabs">
                @foreach ($filters as $key => $f)
                    <button class="inbox-tab {{ $filter === $key ? 'active' : '' }}"
                            wire:click="setFilter('{{ $key }}')">
                        {{ $f['label'] }}
                        @if ($f['count'] > 0)
                            <span class="cnt">{{ $f['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Message list --}}
            <div class="inbox-list">
                @forelse ($messages as $msg)
                    <div class="inbox-item {{ $selectedId === $msg->id ? 'active' : '' }} {{ !$msg->is_read ? 'unread' : '' }}"
                         wire:click="selectMessage({{ $msg->id }})">
                        <div class="inbox-avatar inbox-avatar-{{ $msg->platform }}">
                            {{ strtoupper(substr($msg->sender_username ?: $msg->sender_id, 0, 1)) }}
                            @if (!$msg->is_read)
                                <span class="unread-dot"></span>
                            @endif
                        </div>
                        <div class="inbox-meta">
                            <div class="inbox-name">
                                <span>{{ $msg->sender_username ?: $msg->sender_id }}</span>
                                <span class="inbox-time">{{ $msg->sent_at?->diffForHumans(short: true) }}</span>
                            </div>
                            <div class="inbox-badges">
                                <span class="badge badge-{{ $msg->platform }}">{{ ucfirst($msg->platform) }}</span>
                                <span class="badge {{ match($msg->status) { 'new'=>'badge-new', 'follow-up'=>'badge-fu', default=>'badge-done' } }}">
                                    {{ match($msg->status) { 'new'=>'Baru', 'follow-up'=>'Follow-up', default=>'Selesai' } }}
                                </span>
                            </div>
                            <div class="inbox-preview">{{ $msg->message }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding:32px;text-align:center;color:#475569;font-size:13px;">
                        Tidak ada pesan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── RIGHT PANEL ── --}}
        <div class="inbox-right">
            @if (!$selected)
                <div class="inbox-empty">
                    <div class="inbox-empty-icon">👈</div>
                    <span style="font-size:14px;">Pilih percakapan di sebelah kiri</span>
                </div>
            @else
                {{-- Conversation header --}}
                <div class="conv-header">
                    <div class="conv-user">
                        <div class="inbox-avatar inbox-avatar-{{ $selected->platform }}" style="width:36px;height:36px;font-size:13px;">
                            {{ strtoupper(substr($selected->sender_username ?: $selected->sender_id, 0, 1)) }}
                        </div>
                        <div>
                            <div class="conv-name">
                                {{ $selected->sender_username ?: $selected->sender_id }}
                            </div>
                            <div class="conv-sub">
                                {{ ucfirst($selected->platform) }}
                                &bull;
                                <span class="badge {{ match($selected->status) { 'new'=>'badge-new', 'follow-up'=>'badge-fu', default=>'badge-done' } }}">
                                    {{ match($selected->status) { 'new'=>'Baru', 'follow-up'=>'Follow-up', default=>'Selesai' } }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="conv-actions">
                        @if ($selected->status !== 'resolved')
                            <button class="conv-btn conv-btn-done"
                                    wire:click="markResolved({{ $selected->id }})">
                                ✓ Tandai Selesai
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Thread --}}
                <div class="conv-thread">
                    {{-- Original message --}}
                    <div class="msg-bubble msg-bubble-incoming">
                        <div class="bubble">{{ $selected->message }}</div>
                        <div class="msg-meta">
                            {{ $selected->sender_username }}
                            &bull;
                            {{ $selected->sent_at?->format('d M Y, H:i') }}
                        </div>
                    </div>

                    {{-- Replies --}}
                    @foreach ($selected->replies as $reply)
                        <div class="msg-bubble msg-bubble-outgoing">
                            <div class="bubble">{{ $reply->reply }}</div>
                            <div class="msg-meta">
                                {{ $reply->replier?->name ?? 'Admin' }}
                                &bull;
                                {{ $reply->created_at->format('d M Y, H:i') }}
                                @if (!$reply->is_sent)
                                    &bull; <span style="color:#f59e0b;">⏳ Belum terkirim ke platform</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Reply box --}}
                @if ($selected->status !== 'resolved')
                    <div class="conv-reply">
                        <textarea wire:model="replyText"
                                  placeholder="Ketik balasan..."
                                  rows="2"
                                  wire:keydown.ctrl.enter="sendReply"></textarea>
                        <button class="send-btn"
                                wire:click="sendReply"
                                :disabled="!$wire.replyText.trim()">
                            Kirim
                        </button>
                    </div>
                @else
                    <div style="padding:14px 20px;text-align:center;font-size:12px;color:#475569;border-top:1px solid rgba(255,255,255,.07);">
                        Percakapan ini sudah ditandai selesai.
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
