<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Post Terbaru</x-slot>
        <x-slot name="headerEnd">
            <a href="/admin/posts" style="font-size:12px;color:#14c8aa;text-decoration:none;">Lihat semua →</a>
        </x-slot>

        <style>
            .post-list { display: flex; flex-direction: column; gap: 12px; }
            .post-item { display: flex; align-items: flex-start; gap: 10px; }
            .post-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0; }
            .post-icon-fb { background: #1877F2; }
            .post-icon-ig { background: linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }
            .post-body { flex: 1; min-width: 0; }
            .post-caption { font-size: 13px; color: #111827; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .dark .post-caption { color: #f9fafb; }
            .post-meta { font-size: 11px; color: #9ca3af; margin-top: 3px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
            .post-status { font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 10px; }
            .post-status-published { background: #d1fae5; color: #065f46; }
            .post-status-scheduled { background: #fef3c7; color: #d97706; }
            .post-status-draft     { background: #f3f4f6; color: #6b7280; }
            .post-status-failed    { background: #fee2e2; color: #dc2626; }
            .post-eng { display: flex; gap: 8px; font-size: 11px; color: #6b7280; }
        </style>

        <div class="post-list">
            @forelse ($this->getPosts() as $post)
                @php
                    $totalLikes    = $post->metrics->sum('likes');
                    $totalComments = $post->metrics->sum('comments');
                @endphp
                <div class="post-item">
                    <div class="post-icon {{ $post->platform === 'facebook' ? 'post-icon-fb' : 'post-icon-ig' }}">
                        {{ $post->platform === 'facebook' ? 'FB' : 'IG' }}
                    </div>
                    <div class="post-body">
                        <div class="post-caption">{{ $post->caption }}</div>
                        <div class="post-meta">
                            <span class="post-status post-status-{{ $post->status }}">
                                {{ match($post->status) {
                                    'published' => 'Published',
                                    'scheduled' => 'Scheduled',
                                    'failed'    => 'Failed',
                                    default     => 'Draft',
                                } }}
                            </span>
                            @if($post->isPublished())
                                <span class="post-eng">
                                    <span>❤️ {{ $totalLikes }}</span>
                                    <span>💬 {{ $totalComments }}</span>
                                </span>
                            @elseif($post->isScheduled())
                                <span>📅 {{ $post->scheduled_at?->format('d M H:i') }}</span>
                            @endif
                            <span>{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <p style="font-size:13px;color:#9ca3af;text-align:center;padding:16px 0;">Belum ada post.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
