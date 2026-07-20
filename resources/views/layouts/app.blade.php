<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ATV Arsip') — Kominfo Kota Batu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span>ATV</span><div>Arsip Digital<small>Kominfo Kota Batu</small></div></a>
        <nav>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="sidebar-icon icon-dashboard" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 11.5 12 5l8 6.5v7a1.5 1.5 0 0 1-1.5 1.5H15v-5.5H9V20H5.5A1.5 1.5 0 0 1 4 18.5v-7Z"/></svg>
                </span>
                <span>Dashboard</span>
            </a>
            @php
                $sidebarCategoryCounts = \App\Models\VideoArchive::selectRaw('category, count(*) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category');
                $sidebarTotalArchives = $sidebarCategoryCounts->sum();
                $sidebarActiveCategory = request('category');
                $sidebarArchiveOpen = request()->routeIs('archives.index')
                    || request()->routeIs('archives.show')
                    || request()->routeIs('archives.edit');
            @endphp
            <details class="sidebar-dropdown" {{ $sidebarArchiveOpen ? 'open' : '' }}>
                <summary class="{{ $sidebarArchiveOpen ? 'active' : '' }}">
                    <span class="sidebar-icon icon-archive" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 17.5v-11Zm4 1.25v8.5l7-4.25-7-4.25Z"/></svg>
                    </span>
                    <span>Arsip Video</span>
                    <span class="chevron" aria-hidden="true"></span>
                </summary>
                <div class="sidebar-submenu">
                    <a class="{{ request()->routeIs('archives.index') && ! $sidebarActiveCategory ? 'active' : '' }}" href="{{ route('archives.index') }}">
                        <span class="category-dot all"></span>
                        <span>Semua Video</span>
                        <strong>{{ $sidebarTotalArchives }}</strong>
                    </a>
                    @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                        <a class="{{ $sidebarActiveCategory === $category ? 'active' : '' }}" href="{{ route('archives.index', ['category' => $category]) }}">
                            <span class="category-dot {{ str($category)->slug() }}"></span>
                            <span>{{ $category }}</span>
                            <strong>{{ $sidebarCategoryCounts[$category] ?? 0 }}</strong>
                        </a>
                    @endforeach
                </div>
            </details>
            <a href="{{ route('archives.upload') }}">
                <span class="sidebar-icon icon-upload" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5a1 1 0 0 1 1-1Z"/></svg>
                </span>
                <span>Upload Video</span>
            </a>
            <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <span class="sidebar-icon icon-report" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.8V8h3.2L13 4.8ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h3v2H8V8Z"/></svg>
                </span>
                <span>Laporan</span>
            </a>
        </nav>
        <div class="profile"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div></div>
        <form action="{{ route('logout') }}" method="post">@csrf<button class="logout">Keluar</button></form>
    </aside>
    <div class="sidebar-backdrop" onclick="document.querySelector('.sidebar')?.classList.remove('open')"></div>
    <main class="main">
        <header><button class="menu" onclick="document.querySelector('.sidebar')?.classList.toggle('open')">&#9776;</button><div><small>Sistem Manajemen Arsip</small><strong>ATV Kominfo Kota Batu</strong></div></header>
        <div class="content">
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>



