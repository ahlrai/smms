<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Kalender Jadwal Post</x-slot>

        @php
            $data     = $this->getCalendarData();
            $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        @endphp

        <style>
            .cal-nav { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
            .cal-nav-btn { border:1px solid #e5e7eb; border-radius:8px; padding:6px 14px; font-size:13px; font-weight:500; cursor:pointer; background:transparent; color:#374151; transition:background .15s; }
            .cal-nav-btn:hover { background:#f3f4f6; }
            .dark .cal-nav-btn { border-color:#374151; color:#d1d5db; }
            .dark .cal-nav-btn:hover { background:#1f2937; }
            .cal-nav-title { font-size:15px; font-weight:700; color:#111827; }
            .dark .cal-nav-title { color:#f9fafb; }
            .cal-head { display:grid; grid-template-columns:repeat(7,1fr); margin-bottom:6px; }
            .cal-head-cell { text-align:center; font-size:11px; font-weight:600; color:#6b7280; padding:4px 0; }
            .cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:#e5e7eb; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; }
            .dark .cal-grid { background:#374151; border-color:#374151; }
            .cal-cell { background:#fff; min-height:90px; padding:6px; display:flex; flex-direction:column; gap:3px; cursor:pointer; transition:background .15s; }
            .cal-cell:hover { background:#f0fdf9; }
            .dark .cal-cell { background:#1f2937; }
            .dark .cal-cell:hover { background:rgba(20,200,170,.1); }
            .cal-cell-empty { background:#f9fafb; min-height:90px; }
            .dark .cal-cell-empty { background:#111827; }
            .cal-cell-today { outline:2px solid #14c8aa; outline-offset:-2px; }
            .cal-day { font-size:11px; font-weight:600; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border-radius:50%; color:#374151; flex-shrink:0; }
            .dark .cal-day { color:#d1d5db; }
            .cal-day-today { background:#14c8aa; color:#fff !important; }
            .cal-badge {
            font-size:10px;
            font-weight:500;
            padding:4px;
            border-radius:4px;
            overflow:hidden;
        }
            .cal-badge-fb { background:#dbeafe; color:#1d4ed8; }
            .dark .cal-badge-fb { background:rgba(29,78,216,.25); color:#93c5fd; }
            .cal-badge-ig { background:#fce7f3; color:#be185d; }
            .dark .cal-badge-ig { background:rgba(190,24,93,.25); color:#f9a8d4; }
            .cal-badge-fail { background:#fee2e2; color:#dc2626; }
            .cal-more { font-size:10px; color:#9ca3af; }
            .cal-legend { display:flex; gap:16px; margin-top:12px; font-size:12px; color:#6b7280; align-items:center; flex-wrap:wrap; }
            .cal-legend-item { display:flex; align-items:center; gap:6px; }
            .cal-legend-dot { width:12px; height:12px; border-radius:3px; display:inline-block; }

            /* Modal */
            .cal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; display:flex; align-items:center; justify-content:center; padding:16px; }
            .cal-modal { background:#fff; border-radius:16px; width:100%; max-width:520px; max-height:80vh; overflow-y:auto; box-shadow:0 25px 50px rgba(0,0,0,.25); }
            .dark .cal-modal { background:#1f2937; }
            .cal-modal-hd { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 16px; border-bottom:1px solid #f3f4f6; }
            .dark .cal-modal-hd { border-color:#374151; }
            .cal-modal-title { font-size:15px; font-weight:700; color:#111827; }
            .dark .cal-modal-title { color:#f9fafb; }
            .cal-modal-x { width:28px; height:28px; border-radius:6px; border:none; background:#f3f4f6; color:#6b7280; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; }
            .dark .cal-modal-x { background:#374151; color:#9ca3af; }
            .cal-modal-bd { padding:16px 24px 24px; display:flex; flex-direction:column; gap:12px; }
            .cal-card { border:1px solid #f3f4f6; border-radius:10px; padding:14px; display:flex; flex-direction:column; gap:6px; transition:border-color .15s; }
            .dark .cal-card { border-color:#374151; }
            .cal-card:hover { border-color:#14c8aa; }
            .cal-card-top { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
            .cal-plat { font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px; }
            .cal-plat-fb { background:#dbeafe; color:#1d4ed8; }
            .cal-plat-ig { background:#fce7f3; color:#be185d; }
            .cal-stat { font-size:11px; font-weight:600; padding:2px 8px; border-radius:10px; }
            .cal-stat-published { background:#d1fae5; color:#065f46; }
            .cal-stat-scheduled { background:#fef3c7; color:#d97706; }
            .cal-stat-failed    { background:#fee2e2; color:#dc2626; }
            .cal-stat-draft     { background:#f3f4f6; color:#6b7280; }
            .cal-caption { font-size:13px; color:#374151; line-height:1.5; }
            .dark .cal-caption { color:#d1d5db; }
            .cal-meta { font-size:11px; color:#9ca3af; display:flex; gap:12px; flex-wrap:wrap; }
            .cal-actions { display:flex; gap:8px; margin-top:4px; }
            .cal-btn-outline { font-size:11px; color:#14c8aa; text-decoration:none; border:1px solid #14c8aa; padding:3px 10px; border-radius:6px; }
            .cal-btn-solid { font-size:11px; color:#fff; background:#14c8aa; text-decoration:none; padding:3px 10px; border-radius:6px; }
            .cal-empty { text-align:center; padding:28px 0; color:#9ca3af; }
            .cal-fail-msg { font-size:11px; color:#dc2626; background:#fee2e2; padding:6px 8px; border-radius:6px; }
        </style>

        {{-- Nav --}}
        <div class="cal-nav">
            <button wire:click="previousMonth" class="cal-nav-btn">← Sebelumnya</button>
            <span class="cal-nav-title">{{ $data['monthName'] }}</span>
            <button wire:click="nextMonth" class="cal-nav-btn">Berikutnya →</button>
        </div>

        {{-- Header --}}
        <div class="cal-head">
            @foreach ($dayNames as $day)
                <div class="cal-head-cell">{{ $day }}</div>
            @endforeach
        </div>

        {{-- Grid --}}
        <div class="cal-grid">
            @foreach ($data['grid'] as $week)
                @foreach ($week as $cell)
                    @if ($cell === null)
                        <div class="cal-cell-empty"></div>
                    @else
                        <div class="cal-cell {{ $cell['today'] ? 'cal-cell-today' : '' }}"
                             wire:click="selectDate('{{ $cell['date'] }}')">
                            <span class="cal-day {{ $cell['today'] ? 'cal-day-today' : '' }}">
                                {{ $cell['day'] }}
                            </span>
                            @foreach ($cell['posts']->take(3) as $post)

    <div class="cal-badge {{ $post->status === 'failed'
        ? 'cal-badge-fail'
        : ($post->platform === 'facebook'
            ? 'cal-badge-fb'
            : 'cal-badge-ig') }}">

        <div style="font-size:10px;font-weight:700;">
            {{ \Carbon\Carbon::parse($post->scheduled_at)->format('H:i') }}
        </div>

        <div>
            {{ \Illuminate\Support\Str::limit(
            $post->title,
            18
        ) }}
        </div>

    </div>

@endforeach
                            @if ($cell['posts']->count() > 3)
                                <span class="cal-more">+{{ $cell['posts']->count() - 3 }} lainnya</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="cal-legend">
            <span class="cal-legend-item"><span class="cal-legend-dot" style="background:#dbeafe;"></span>Facebook</span>
            <span class="cal-legend-item"><span class="cal-legend-dot" style="background:#fce7f3;"></span>Instagram</span>
            <span class="cal-legend-item"><span class="cal-legend-dot" style="background:#fee2e2;"></span>Gagal</span>
            <span style="margin-left:auto;font-size:11px;color:#9ca3af;">💡 Klik tanggal untuk lihat detail</span>
        </div>

        {{-- Modal --}}
        @if ($showModal && $selectedDate)
            @php
                $selPosts = $this->getSelectedPosts();
                $selLabel = \Carbon\Carbon::parse($selectedDate)->locale('id')->translatedFormat('l, d F Y');
            @endphp
            <div class="cal-overlay" wire:click.self="closeModal">
                <div class="cal-modal">
                    <div class="cal-modal-hd">
                        <h3 class="cal-modal-title">📅 {{ $selLabel }}</h3>
                        <button class="cal-modal-x" wire:click="closeModal">✕</button>
                    </div>
                    <div class="cal-modal-bd">
                        @if ($selPosts->isEmpty())
                            <div class="cal-empty">
                                <div style="font-size:36px;margin-bottom:8px;">📭</div>
                                Belum ada post di tanggal ini.<br>
                                <a href="/admin/posts/create" style="color:#14c8aa;font-size:12px;margin-top:6px;display:inline-block;">+ Buat post baru</a>
                            </div>
                        @else
                            <p style="font-size:12px;color:#9ca3af;">{{ $selPosts->count() }} post ditemukan</p>
                            @foreach ($selPosts as $post)
                                <div class="cal-card">
                                    <div class="cal-card-top">
                                        <span class="cal-plat {{ $post->platform === 'facebook' ? 'cal-plat-fb' : 'cal-plat-ig' }}">
                                            {{ $post->platform === 'facebook' ? 'Facebook' : 'Instagram' }}
                                        </span>
                                        <span class="cal-stat cal-stat-{{ $post->status }}">
                                            {{ match($post->status) {
                                                'published' => '✅ Published',
                                                'scheduled' => '⏰ Scheduled',
                                                'failed'    => '❌ Failed',
                                                default     => '📝 Draft',
                                            } }}
                                        </span>
                                    </div>
                                    <div style="font-size:14px;font-weight:600;color:#111827;">
                                        {{ $post->title }}
                                    </div>

                                    <div class="cal-caption">
                                        {{ \Illuminate\Support\Str::limit($post->caption, 160) }}
                                    </div>
                                    <div class="cal-meta">

                                        <span>
                                            👤 {{ $post->socialAccount?->username }}
                                        </span>

                                        @if ($post->scheduled_at)

                                            <span>
                                                📅 {{ \Carbon\Carbon::parse($post->scheduled_at)->format('d M Y') }}
                                            </span>

                                            <span>
                                                ⏰ {{ \Carbon\Carbon::parse($post->scheduled_at)->format('H:i') }} WIB
                                            </span>

                                        @endif

                                        @if ($post->published_at)

                                            <span>
                                                ✅ {{ \Carbon\Carbon::parse($post->published_at)->format('d M Y H:i') }} WIB
                                            </span>

                                        @endif

                                    </div>
                                    @if ($post->fail_reason)
                                        <div class="cal-fail-msg">⚠️ {{ $post->fail_reason }}</div>
                                    @endif
                                    <div class="cal-actions">
                                        <a href="/admin/posts" class="cal-btn-solid">
                                            📋 Lihat Daftar Post
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
