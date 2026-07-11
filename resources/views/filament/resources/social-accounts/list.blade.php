<x-filament-panels::page>
    @php $accounts = $this->getAccounts(); @endphp

    <style>
        .sa-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sa-card {
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(255,255,255,.07);
            background: rgba(255,255,255,.03);
        }

        .sa-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 26px;
        }

        .sa-icon-fb {
            background: linear-gradient(135deg,#1877F2,#0d5fd1);
        }

        .sa-icon-ig {
            background: radial-gradient(
                circle at 30% 110%,
                #f09433 0%,
                #e6683c 25%,
                #dc2743 50%,
                #cc2366 75%,
                #bc1888 100%
            );
        }

        .sa-body {
            flex: 1;
            min-width: 0;
        }

        .sa-name {
            font-size: 15px;
            font-weight: 700;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .sa-plat {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 10px;
        }

        .sa-plat-fb {
            background: rgba(24,119,242,.2);
            color: #60a5fa;
        }

        .sa-plat-ig {
            background: rgba(220,39,67,.2);
            color: #f9a8d4;
        }

        .sa-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .sa-btn {
            font-size: 12px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: opacity .15s;
        }

        .sa-btn:hover {
            opacity: .85;
        }

        .sa-btn-muted {
            background: rgba(255,255,255,.06);
            color: #94a3b8;
            border: 1px solid rgba(255,255,255,.08);
        }

        .sa-btn-del {
            background: rgba(239,68,68,.08);
            color: #f87171;
            border: 1px solid rgba(239,68,68,.15);
        }

        .sa-empty {
            text-align: center;
            padding: 40px;
            color: #475569;
        }
    </style>

    <div class="sa-grid">
        @forelse ($accounts as $account)

            <div class="sa-card">

                {{-- Icon --}}
                <div class="sa-icon {{ $account->platform === 'facebook' ? 'sa-icon-fb' : 'sa-icon-ig' }}">
                    @if ($account->platform === 'facebook')
                        <svg viewBox="0 0 24 24" width="28" fill="white">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    @else
                        <svg viewBox="0 0 24 24" width="26" fill="white">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    @endif
                </div>

                {{-- Info --}}
                <div class="sa-body">

                    <div class="sa-name">
                        {{ $account->username }}

                        <span class="sa-plat {{ $account->platform === 'facebook' ? 'sa-plat-fb' : 'sa-plat-ig' }}">
                            {{ ucfirst($account->platform) }}
                        </span>
                    </div>

                    @if ($account->display_name)
                        <div style="
                            font-size:12px;
                            color:#64748b;
                            margin-top:4px;
                        ">
                            🏷️ {{ $account->display_name }}
                        </div>
                    @endif

                </div>

                {{-- Actions --}}
                <div class="sa-actions">

                    <a href="/admin/social-accounts/{{ $account->id }}/edit"
                       class="sa-btn sa-btn-muted">
                        ✏️ Edit
                    </a>

                    <button class="sa-btn sa-btn-del"
                            wire:click="deleteAccount({{ $account->id }})">
                        🗑 Hapus
                    </button>

                </div>

            </div>

        @empty

            <div class="sa-empty">
                <div style="font-size:40px;margin-bottom:12px;">🔗</div>

                <p style="font-size:14px;font-weight:600;color:#94a3b8;">
                    Belum ada akun sosial terhubung
                </p>

                <p style="font-size:12px;margin-top:4px;">
                    Klik tombol "Hubungkan Akun" di atas untuk mulai.
                </p>
            </div>

        @endforelse
    </div>

        {{-- Panduan --}}
    <div style="
        border:1px dashed #1e3a4a;
        border-radius:12px;
        padding:20px 24px;
        margin-top:12px;
    ">
        <div style="
            font-size:14px;
            font-weight:700;
            color:#38bdf8;
            margin-bottom:12px;
            display:flex;
            align-items:center;
            gap:8px;
        ">
            <span>📋</span>
            Cara Menghubungkan Akun
        </div>

        <ol style="
            padding-left:18px;
            display:flex;
            flex-direction:column;
            gap:6px;
        ">
            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                Sebelum akun media sosial dapat dihubungkan ke dalam sistem, administrator harus melakukan konfigurasi aplikasi <strong>SMMS2</strong> pada <strong>Meta Developer</strong>.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                1. Administrator menambahkan akun Facebook yang akan digunakan sebagai administrator pada aplikasi SMMS2 di Meta Developer.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                2. Administrator mengatur permission (izin akses) yang diperlukan pada Graph API.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                3. Setelah konfigurasi selesai, buka menu Social Media → Akun Sosial pada sistem.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                4. Klik tombol Hubungkan Akun.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                5. Sistem akan mengarahkan pengguna ke halaman Login dan Otorisasi Meta.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                6. Login menggunakan akun Facebook yang telah didaftarkan sebagai administrator pada aplikasi SMMS2 di Meta Developer.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                7. Berikan seluruh izin akses (permission) yang diminta oleh Meta.
            </li>

            <li style="font-size:13px;color:#64748b;line-height:1.6;">
                8. Akun Facebook Page dan Instagram Business yang berhasil dihubungkan akan otomatis ditampilkan pada daftar akun sosial.
            </li>



        </ol>
    </div>
</x-filament-panels::page>