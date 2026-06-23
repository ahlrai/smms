<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Insights Platform</x-slot>

        <style>
            .pi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; }
            .pi-card {
                border-radius:16px; padding:20px;
                border:1px solid rgba(255,255,255,.07);
                background:rgba(255,255,255,.03);
            }
            .pi-header { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
            .pi-icon {
                width:44px; height:44px; border-radius:12px;
                display:flex; align-items:center; justify-content:center; flex-shrink:0;
            }
            .pi-icon-fb { background:linear-gradient(135deg,#1877F2,#0d5fd1); }
            .pi-icon-ig {
                background:radial-gradient(circle at 30% 110%,
                    #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
            }
            .pi-title { font-size:15px; font-weight:700; color:#f1f5f9; }
            .pi-sub   { font-size:11px; color:#64748b; margin-top:2px; }
            .pi-stats { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
            .pi-stat  { background:rgba(255,255,255,.04); border-radius:10px; padding:12px; }
            .pi-stat-num { font-size:22px; font-weight:700; color:#f1f5f9; }
            .pi-stat-lbl { font-size:10px; color:#64748b; margin-top:3px; text-transform:uppercase; letter-spacing:.05em; }
            .pi-accounts { border-top:1px solid rgba(255,255,255,.06); padding-top:12px; }
            .pi-account-row {
                display:flex; align-items:center; justify-content:space-between;
                padding:5px 0;
            }
            .pi-account-name { font-size:12px; color:#cbd5e1; }
            .pi-audience     { font-size:12px; font-weight:600; color:#94a3b8; }
            .pi-empty        { font-size:12px; color:#475569; text-align:center; padding:8px 0; }
        </style>

        @php
            $hasFb = !empty($platforms['facebook']['accounts']);
            $hasIg = !empty($platforms['instagram']['accounts']);
        @endphp

        @if(!$hasFb && !$hasIg)
            <p style="color:#475569; font-size:13px; text-align:center; padding:20px 0;">
                Belum ada akun yang terhubung.
            </p>
        @else
            <div class="pi-grid">

                {{-- Facebook --}}
                @if($hasFb)
                    @php $fb = $platforms['facebook']; @endphp
                    <div class="pi-card">
                        <div class="pi-header">
                            <div class="pi-icon pi-icon-fb">
                                <svg viewBox="0 0 24 24" width="22" fill="white">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="pi-title">Facebook</div>
                                <div class="pi-sub">{{ count($fb['accounts']) }} akun terhubung</div>
                            </div>
                        </div>

                        <div class="pi-stats">
                            <div class="pi-stat">
                                <div class="pi-stat-num">{{ number_format($fb['total_audience']) }}</div>
                                <div class="pi-stat-lbl">Fans</div>
                            </div>
                            <div class="pi-stat">
                                <div class="pi-stat-num">{{ $fb['total_posts'] }}</div>
                                <div class="pi-stat-lbl">Post Terbit</div>
                            </div>
                        </div>

                        @if(count($fb['accounts']) > 1)
                            <div class="pi-accounts">
                                @foreach($fb['accounts'] as $acc)
                                    <div class="pi-account-row">
                                        <span class="pi-account-name">&#64;{{ $acc['username'] }}</span>
                                        <span class="pi-audience">{{ number_format($acc['audience']) }} fans</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                &#64;{{ $fb['accounts'][0]['username'] ?? '' }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Instagram --}}
                @if($hasIg)
                    @php $ig = $platforms['instagram']; @endphp
                    <div class="pi-card">
                        <div class="pi-header">
                            <div class="pi-icon pi-icon-ig">
                                <svg viewBox="0 0 24 24" width="20" fill="white">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="pi-title">Instagram</div>
                                <div class="pi-sub">{{ count($ig['accounts']) }} akun terhubung</div>
                            </div>
                        </div>

                        <div class="pi-stats">
                            <div class="pi-stat">
                                <div class="pi-stat-num">{{ number_format($ig['total_audience']) }}</div>
                                <div class="pi-stat-lbl">Pengikut</div>
                            </div>
                            <div class="pi-stat">
                                <div class="pi-stat-num">{{ $ig['total_posts'] }}</div>
                                <div class="pi-stat-lbl">Post Terbit</div>
                            </div>
                        </div>

                        @if(count($ig['accounts']) > 1)
                            <div class="pi-accounts">
                                @foreach($ig['accounts'] as $acc)
                                    <div class="pi-account-row">
                                        <span class="pi-account-name">&#64;{{ $acc['username'] }}</span>
                                        <span class="pi-audience">{{ number_format($acc['audience']) }} pengikut</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                &#64;{{ $ig['accounts'][0]['username'] ?? '' }}
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
