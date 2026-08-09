<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak - ATV Arsip</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="error-page-body">
    <main class="error-page">
        <section class="error-panel">
            <span class="error-code">403</span>
            <h1>Akses ditolak</h1>
            <p>Anda tidak memiliki izin untuk membuka atau menjalankan aksi ini.</p>
            <div class="error-actions">
                <a class="btn primary" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">Kembali</a>
                <a class="btn" href="{{ route('dashboard') }}">Dashboard</a>
            </div>
        </section>
    </main>
</body>
</html>
