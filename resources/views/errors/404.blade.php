<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman Tidak Ditemukan - ATV Arsip</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="error-page-body">
    <main class="error-page">
        <section class="error-panel">
            <span class="error-code">404</span>
            <h1>Halaman tidak ditemukan</h1>
            <p>Alamat yang dibuka tidak tersedia, sudah dipindahkan, atau datanya tidak ditemukan.</p>
            <div class="error-actions">
                <a class="btn primary" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="btn" href="{{ route('archives.index') }}">Arsip Video</a>
            </div>
        </section>
    </main>
</body>
</html>
