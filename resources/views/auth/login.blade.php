<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - ATV Arsip</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-showcase" aria-label="Ringkasan sistem arsip">
            <div class="login-brand-wrap">
                <img class="login-brand-logo" src="/images/atv-logo-crop.png" alt="Logo ATV">
                <div>
                    <strong>Arsip Digital</strong>
                    <small>Diskominfo Kota Batu</small>
                </div>
            </div>

            <div class="login-copy">
                <span class="login-eyebrow">Sistem Manajemen Arsip</span>
                <h1>Kelola Arsip Digital ATV Diskominfo Kota Batu</h1>
                <p>Masuk untuk mengatur katalog video, status tayang, laporan, dan riwayat aktivitas dalam satu dashboard.</p>
            </div>

            <div class="login-stat-grid" aria-label="Ringkasan data dashboard">
                <div>
                    <span>Total Video</span>
                    <strong>{{ number_format($loginStats['total']) }}</strong>
                </div>
                <div>
                    <span>Siap Tayang</span>
                    <strong>{{ number_format($loginStats['ready']) }}</strong>
                </div>
                <div>
                    <span>Sudah Tayang</span>
                    <strong>{{ number_format($loginStats['aired']) }}</strong>
                </div>
            </div>

            <div class="login-preview-card">
                <div class="login-preview-head">
                    <div>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <strong>Arsip Terbaru</strong>
                </div>
                @forelse($latestArchives as $archive)
                    <div class="login-preview-row {{ $loop->first ? 'active' : '' }}">
                        <i></i>
                        <div>
                            <strong>{{ $archive->title }}</strong>
                            <small>{{ $archive->status }} - {{ $archive->category }}</small>
                        </div>
                        <b>{{ $archive->progress }}%</b>
                    </div>
                @empty
                    <div class="login-preview-row active">
                        <i></i>
                        <div>
                            <strong>Belum ada arsip video</strong>
                            <small>Upload video pertama dari dashboard</small>
                        </div>
                        <b>0%</b>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="login-panel" aria-label="Form masuk">
            <div class="login-card">
                <div class="login-secure-badge">
                    <span></span>
                    Akses admin terverifikasi
                </div>

                <div class="login-card-head">
                    <span class="login-icon" aria-hidden="true"></span>
                    <div>
                        <h2>Selamat datang</h2>
                        <p>Masuk ke akun pengelola arsip ATV.</p>
                    </div>
                </div>

                <form method="post" action="{{ route('login.store') }}">
                    @csrf

                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@atv.kominfo">
                    </label>

                    <label>
                        Kata sandi
                        <input type="password" name="password" required placeholder="atv12345">
                    </label>

                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror

                    <div class="login-options">
                        <label class="check">
                            <input type="checkbox" name="remember">
                            <span>Ingat saya</span>
                        </label>
                    </div>

                    <button class="btn primary full login-submit" type="submit">
                        <span>Masuk</span>
                        <i aria-hidden="true">-&gt;</i>
                    </button>
                </form>

                <div class="login-credential-box" aria-label="Informasi akun masuk">
                    <span>Akun tersedia</span>
                    <div class="login-credential-row">
                        <strong>Super Admin</strong>
                        <div>
                            <small>Email</small>
                            <code>admin@atv.kominfo</code>
                        </div>
                        <div>
                            <small>Kata sandi</small>
                            <code>atv12345</code>
                        </div>
                    </div>
                    <div class="login-credential-row">
                        <strong>Admin</strong>
                        <div>
                            <small>Email</small>
                            <code>staff@atv.kominfo</code>
                        </div>
                        <div>
                            <small>Kata sandi</small>
                            <code>staff12345</code>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
