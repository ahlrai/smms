<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Social Media Manager') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #14c8aa;
            --primary-d: #0fa88e;
            --dark:      #0f172a;
            --dark2:     #1e293b;
            --muted:     #64748b;
            --light:     #f8fafc;
            --white:     #ffffff;
            --fb:        #1877F2;
            --ig:        #E1306C;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── NAV ── */
        nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: rgba(15, 23, 42, .85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .logo svg { width: 32px; height: 32px; }

        .nav-login {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1.4rem;
            background: var(--primary);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            font-size: .9rem;
            text-decoration: none;
            transition: background .2s;
        }
        .nav-login:hover { background: var(--primary-d); }

        /* ── HERO ── */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8rem 2rem 4rem;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(20,200,170,.18) 0%, transparent 70%),
                var(--dark);
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(20,200,170,.12);
            border: 1px solid rgba(20,200,170,.3);
            color: var(--primary);
            font-size: .8rem;
            font-weight: 600;
            padding: .35rem 1rem;
            border-radius: 100px;
            margin-bottom: 1.5rem;
            letter-spacing: .04em;
        }

        h1 {
            font-size: clamp(2.2rem, 6vw, 4rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1.4rem;
            background: linear-gradient(135deg, #fff 40%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 1.15rem;
            color: #94a3b8;
            max-width: 580px;
            margin: 0 auto 2.5rem;
            line-height: 1.7;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .85rem 2.2rem;
            background: var(--primary);
            color: #fff;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            box-shadow: 0 0 30px rgba(20,200,170,.35);
            transition: all .2s;
        }
        .btn-primary:hover {
            background: var(--primary-d);
            transform: translateY(-2px);
            box-shadow: 0 6px 40px rgba(20,200,170,.5);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .85rem 2.2rem;
            border: 1.5px solid rgba(255,255,255,.18);
            color: #cbd5e1;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* ── PLATFORMS ── */
        .platforms {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 4rem;
            flex-wrap: wrap;
        }
        .platform-badge {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem 1.2rem;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 100px;
            font-size: .85rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-fb { background: var(--fb); }
        .dot-ig { background: var(--ig); }

        /* ── FEATURES ── */
        .section {
            padding: 6rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-label {
            text-align: center;
            font-size: .85rem;
            font-weight: 700;
            letter-spacing: .1em;
            color: var(--primary);
            text-transform: uppercase;
            margin-bottom: .75rem;
        }

        .section-title {
            text-align: center;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-sub {
            text-align: center;
            color: #64748b;
            font-size: 1.05rem;
            max-width: 520px;
            margin: 0 auto 3.5rem;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .feature-card {
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px;
            padding: 1.8rem;
            transition: border-color .2s, transform .2s;
        }
        .feature-card:hover {
            border-color: rgba(20,200,170,.3);
            transform: translateY(-4px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(20,200,170,.12);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .feature-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: .5rem;
        }

        .feature-card p {
            color: #64748b;
            font-size: .9rem;
            line-height: 1.65;
        }

        /* ── STATS ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin: 4rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px;
        }
        .stat-number {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: .4rem;
        }
        .stat-label {
            font-size: .85rem;
            color: #64748b;
        }

        /* ── ROLES ── */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .role-card {
            background: var(--dark2);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 16px;
            padding: 2rem;
        }
        .role-badge {
            display: inline-block;
            padding: .3rem .9rem;
            border-radius: 100px;
            font-size: .8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .role-admin { background: rgba(20,200,170,.15); color: var(--primary); }
        .role-staff { background: rgba(99,102,241,.15); color: #818cf8; }
        .role-card h3 { font-size: 1.1rem; margin-bottom: .75rem; }
        .role-perms { list-style: none; display: flex; flex-direction: column; gap: .5rem; }
        .role-perms li {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .88rem;
            color: #94a3b8;
        }
        .role-perms li::before {
            content: '✓';
            color: var(--primary);
            font-weight: 700;
            font-size: .9rem;
        }

        /* ── CTA BOTTOM ── */
        .cta-section {
            text-align: center;
            padding: 6rem 2rem;
            background: radial-gradient(ellipse 60% 60% at 50% 50%, rgba(20,200,170,.12) 0%, transparent 70%);
        }
        .cta-section h2 {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .cta-section p {
            color: #64748b;
            margin-bottom: 2.5rem;
            font-size: 1.05rem;
        }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid rgba(255,255,255,.06);
            padding: 2rem;
            text-align: center;
            color: #334155;
            font-size: .85rem;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 640px) {
            nav { padding: .9rem 1.2rem; }
            .hero-cta { flex-direction: column; align-items: center; }
            .btn-primary, .btn-outline { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- NAV -->
    <nav>
        <a href="/" class="logo">
            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" rx="8" fill="#14c8aa" fill-opacity=".15"/>
                <path d="M8 20l6-8 4 5 3-3 5 6" stroke="#14c8aa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="24" cy="10" r="3" fill="#14c8aa"/>
            </svg>
            {{ config('app.name', 'SocialManager') }}
        </a>
        <a href="/admin/login" class="nav-login">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Masuk
        </a>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div>
            <div class="hero-badge">
                🚀&nbsp; Social Media Management System
            </div>
            <h1>Kelola Semua Media Sosial<br>dalam Satu Dashboard</h1>
            <p class="hero-sub">
                Platform terpadu untuk mengelola konten Facebook &amp; Instagram,
                membalas komentar, memantau analitik, dan menjadwalkan posting —
                semuanya dari satu panel yang mudah digunakan.
            </p>
            <div class="hero-cta">
                <a href="/admin/login" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Masuk ke Dashboard
                </a>
                <a href="#features" class="btn-outline">
                    Lihat Fitur
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </div>

            <div class="platforms">
                <span class="platform-badge"><span class="dot dot-fb"></span> Facebook</span>
                <span class="platform-badge"><span class="dot dot-ig"></span> Instagram</span>
                <span class="platform-badge">📊 Analytics</span>
                <span class="platform-badge">📅 Scheduler</span>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <div style="max-width:1200px;margin:0 auto;padding:0 2rem;">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">2</div>
                <div class="stat-label">Platform Terintegrasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">2</div>
                <div class="stat-label">Level Akses (Role)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">∞</div>
                <div class="stat-label">Post Terjadwal</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Real-time</div>
                <div class="stat-label">Sinkronisasi Komentar</div>
            </div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="section" id="features">
        <div class="section-label">Fitur Sistem</div>
        <h2 class="section-title">Semua yang Anda Butuhkan</h2>
        <p class="section-sub">Sistem manajemen media sosial lengkap yang dirancang untuk tim dan organisasi modern.</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Jadwalkan Posting</h3>
                <p>Buat dan jadwalkan konten ke Facebook Page dan Instagram Business Account. Sistem akan mempublish otomatis sesuai waktu yang ditentukan.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Manajemen Komentar</h3>
                <p>Tarik semua komentar dari Facebook & Instagram ke satu dashboard. Balas komentar langsung dari sistem tanpa berpindah aplikasi.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📩</div>
                <h3>Pesan Masuk (DM)</h3>
                <p>Kelola pesan langsung dari Facebook Messenger dan Instagram DM. Tandai status, beri balasan, dan pantau follow-up dalam satu tempat.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Dashboard Analytics</h3>
                <p>Pantau likes, komentar, shares, reach, dan impressions melalui grafik mingguan dan bulanan yang informatif secara real-time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔗</div>
                <h3>Koneksi OAuth Aman</h3>
                <p>Hubungkan akun Facebook Page dan Instagram Business Account melalui proses OAuth resmi. Token disimpan terenkripsi dan diperbarui otomatis.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Multi-User & Role</h3>
                <p>Kelola tim dengan role Admin dan Staff. Admin mengatur akses, Staff mengelola konten sesuai izin yang diberikan per akun sosial.</p>
            </div>
        </div>
    </section>

    <!-- ROLES -->
    <section class="section" style="padding-top:0">
        <div class="section-label">Role & Akses</div>
        <h2 class="section-title">Kontrol Akses yang Fleksibel</h2>
        <p class="section-sub">Setiap anggota tim memiliki akses yang sesuai dengan tanggung jawabnya.</p>

        <div class="roles-grid">
            <div class="role-card">
                <span class="role-badge role-admin">Admin</span>
                <h3>Akses Penuh</h3>
                <ul class="role-perms">
                    <li>Manajemen user & role</li>
                    <li>Hubungkan akun Facebook & Instagram</li>
                    <li>Publish & jadwalkan post</li>
                    <li>Balas komentar & pesan</li>
                    <li>Lihat semua analytics</li>
                    <li>Atur izin per staff per akun</li>
                </ul>
            </div>
            <div class="role-card">
                <span class="role-badge role-staff">Staff</span>
                <h3>Akses Terbatas</h3>
                <ul class="role-perms">
                    <li>Buat & jadwalkan post</li>
                    <li>Balas komentar</li>
                    <li>Balas pesan</li>
                    <li>Lihat metrics dasar</li>
                    <li>Akses dikontrol oleh Admin</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <h2>Siap Mulai Mengelola Media Sosial Anda?</h2>
        <p>Masuk ke dashboard dan mulai kelola konten secara profesional.</p>
        <a href="/admin/login" class="btn-primary" style="display:inline-flex;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Masuk ke Dashboard
        </a>
    </section>

    <!-- FOOTER -->
    <footer>
        &copy; {{ date('Y') }} {{ config('app.name', 'Social Media Manager') }}. Built with Laravel &amp; Filament.
    </footer>

</body>
</html>
