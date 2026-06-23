<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Post Terbaru</x-slot>
        <x-slot name="headerEnd">
            <a href="/admin/posts" style="font-size:12px; color:#14c8aa; text-decoration:none;">Lihat semua →</a>
        </x-slot>

        <style>
            .pw-card {
                border-radius: 12px;
                padding: 14px 16px;
                display: flex;
                align-items: flex-start;
                gap: 14px;
                border: 1px solid rgba(255,255,255,.07);
                background: rgba(255,255,255,.03);
            }
            .pw-icon {
                width: 44px; height: 44px;
                border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
                font-size: 11px; font-weight: 800; color: white; letter-spacing: .5px;
            }
            .pw-icon-fb { background: linear-gradient(135deg,#1877F2,#0d5fd1); }
            .pw-icon-ig {
                background: radial-gradient(circle at 30% 110%,
                    #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            }
            .pw-caption { font-size: 13px; font-weight: 600; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .pw-meta    { margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
            .pw-time    { font-size: 11px; color: #64748b; }
            .pw-eng     { font-size: 11px; color: #64748b; display: flex; gap: 8px; }
            .pw-badge   { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; }
            .pw-published { background: rgba(74,222,128,.12);  color: #4ade80; }
            .pw-scheduled { background: rgba(251,191,36,.12);  color: #fbbf24; }
            .pw-failed    { background: rgba(248,113,113,.12); color: #f87171; }
            .pw-draft     { background: rgba(148,163,184,.1);  color: #94a3b8; }
        </style>

        <div style="display:flex; flex-direction:column; gap:8px;">
            @forelse ($this->getPosts() as $post)
                @php
                    $totalLikes    = $post->metrics->sum('likes');
                    $totalComments = $post->metrics->sum('comments');
                    $statusClass   = match($post->status) {
                        'published' => 'pw-published',
                        'scheduled' => 'pw-scheduled',
                        'failed'    => 'pw-failed',
                        default     => 'pw-draft',
                    };
                    $statusLabel   = match($post->status) {
                        'published' => 'Published',
                        'scheduled' => 'Scheduled',
                        'failed'    => 'Failed',
                        default     => 'Draft',
                    };
                @endphp

                <div class="pw-card">
                    <div class="pw-icon {{ $post->platform === 'facebook' ? 'pw-icon-fb' : 'pw-icon-ig' }}">
                        {{ $post->platform === 'facebook' ? 'FB' : 'IG' }}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="pw-caption">{{ $post->caption }}</div>
                        <div class="pw-meta">
                            <span class="pw-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($post->isPublished())
                                <span class="pw-eng">
                                    <span>❤ {{ $totalLikes }}</span>
                                    <span>💬 {{ $totalComments }}</span>
                                </span>
                            @elseif($post->isScheduled())
                                <span class="pw-time">📅 {{ $post->scheduled_at?->format('d M H:i') }}</span>
                            @endif
                            <span class="pw-time">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <p style="font-size:13px; color:#475569; text-align:center; padding:20px 0;">Belum ada post.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
