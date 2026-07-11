<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Komentar Terbaru</x-slot>
        <x-slot name="headerEnd">
            <a href="/admin/comments" style="font-size:12px; color:#14c8aa; text-decoration:none;">Lihat semua →</a>
        </x-slot>

        <style>
            .cw-card {
                border-radius: 12px;
                padding: 14px 16px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                border: 1px solid rgba(255,255,255,.07);
                background: rgba(255,255,255,.03);
            }
            .cw-avatar {
                width: 40px; height: 40px;
                border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-size: 14px; font-weight: 700; color: white;
                flex-shrink: 0;
            }
            .cw-avatar-fb { background: linear-gradient(135deg,#1877F2,#0d5fd1); }
            .cw-avatar-ig {
                background: radial-gradient(circle at 30% 110%,
                    #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            }
            .cw-name    { font-size: 13px; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
            .cw-post    { font-size: 11px; color: #64748b; margin-top: 1px; }
            .cw-content { font-size: 12px; color: #94a3b8; margin-top: 4px; }
            .cw-time    { font-size: 11px; color: #475569; margin-top: 3px; }
            .cw-dot     { width: 7px; height: 7px; background: #f59e0b; border-radius: 50%; flex-shrink: 0; }
            .cw-badge   { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; }
            .cw-replied { background: rgba(74,222,128,.12); color: #4ade80; }
            .cw-pending { background: rgba(251,191,36,.12);  color: #fbbf24; }
        </style>

        <div style="display:flex; flex-direction:column; gap:8px;">
            @forelse ($comments as $comment)
                @php
                    $platform  = $comment->socialAccount?->platform ?? $comment->platform;
                    $postTitle = $comment->post?->title
                        ?: \Illuminate\Support\Str::words($comment->post?->caption, 6, '...');
                @endphp
                <div class="cw-card">
                    <div class="cw-avatar {{ $platform === 'facebook' ? 'cw-avatar-fb' : 'cw-avatar-ig' }}">
                        {{ strtoupper(substr($comment->commenter_username, 0, 1)) }}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="cw-name">
                            {{ $comment->commenter_username }}
                            @if(!$comment->is_replied)
                                <span class="cw-dot" title="Belum dibalas"></span>
                            @endif
                            <span class="cw-badge {{ $comment->is_replied ? 'cw-replied' : 'cw-pending' }}">
                                {{ $comment->is_replied ? 'Dibalas' : 'Pending' }}
                            </span>
                        </div>
                        @if($postTitle)
                            <div class="cw-post">{{ $postTitle }}</div>
                        @endif
                        <div class="cw-content">{{ \Illuminate\Support\Str::limit($comment->content, 80) }}</div>
                        <div class="cw-time">
                            {{ $comment->commented_at?->diffForHumans() }} · {{ ucfirst($platform) }}
                        </div>
                    </div>
                </div>
            @empty
                <p style="font-size:13px; color:#475569; text-align:center; padding:20px 0;">Belum ada komentar.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
