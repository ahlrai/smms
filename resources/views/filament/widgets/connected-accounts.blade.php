<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Akun Sosial Terhubung</x-slot>

        <style>
            .ca-card {
                border-radius: 14px;
                padding: 16px 18px;
                display: flex;
                align-items: center;
                gap: 14px;
                border: 1px solid rgba(255,255,255,.07);
                background: rgba(255,255,255,.03);
            }
            .ca-icon {
                width: 48px; height: 48px;
                border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            }
            .ca-icon-fb { background: linear-gradient(135deg,#1877F2,#0d5fd1); }
            .ca-icon-ig {
                background: radial-gradient(circle at 30% 110%,
                    #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            }
            .ca-name { font-size: 14px; font-weight: 700; color: #f1f5f9; }
            .ca-sub  { font-size: 11px; color: #64748b; margin-top: 2px; }
            .ca-plat {
                font-size: 10px; font-weight: 600;
                padding: 2px 8px; border-radius: 10px;
            }
            .ca-plat-fb { background: rgba(24,119,242,.2);  color: #60a5fa; }
            .ca-plat-ig { background: rgba(220,39,67,.2);   color: #f9a8d4; }
            .ca-ok  { font-size: 12px; font-weight: 600; color: #4ade80; }
            .ca-exp { font-size: 12px; font-weight: 600; color: #f87171; }
        </style>

        @if($accounts->isEmpty())
            <div style="text-align:center; padding:24px 0; color:#475569;">
                <p style="font-size:13px;">Belum ada akun yang terhubung.</p>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($accounts as $account)
                    <div class="ca-card">

                        {{-- Icon --}}
                        <div class="ca-icon {{ $account->platform === 'facebook' ? 'ca-icon-fb' : 'ca-icon-ig' }}">
                            @if($account->platform === 'facebook')
                                <svg viewBox="0 0 24 24" width="24" fill="white">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            @else
                                <svg viewBox="0 0 24 24" width="22" fill="white">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span class="ca-name">&#64;{{ $account->username }}</span>
                                <span class="ca-plat {{ $account->platform === 'facebook' ? 'ca-plat-fb' : 'ca-plat-ig' }}">
                                    {{ ucfirst($account->platform) }}
                                </span>
                            </div>
                            @if($account->display_name)
                                <p class="ca-sub">🏷 {{ $account->display_name }}</p>
                            @endif
                        </div>

                        {{-- Status --}}
                        @if($account->isTokenExpired())
                            <span class="ca-exp">✕ Expired</span>
                        @else
                            <span class="ca-ok">✓ Aktif</span>
                        @endif

                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
