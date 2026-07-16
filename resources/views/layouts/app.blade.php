<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ATV Arsip') — Kominfo Kota Batu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>ATV</span><div>Arsip Digital<small>Kominfo Kota Batu</small></div></a>
        <nav>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ <span>Dashboard</span></a>
            <a class="{{ request()->routeIs('archives.*') ? 'active' : '' }}" href="{{ route('archives.index') }}">▣ <span>Arsip Video</span></a>
            <a href="{{ route('archives.create') }}">＋ <span>Upload Video</span></a>
        </nav>
        <div class="profile"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div></div>
        <form action="{{ route('logout') }}" method="post">@csrf<button class="logout">Keluar</button></form>
    </aside>
    <main class="main">
        <header><button class="menu" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button><div><small>Sistem Manajemen Arsip</small><strong>ATV Kominfo Kota Batu</strong></div></header>
        <div class="content">
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
