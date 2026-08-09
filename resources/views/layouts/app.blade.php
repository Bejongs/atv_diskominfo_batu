<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ATV Arsip') - Kominfo Kota Batu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/report-overrides.css?v=2">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <img class="brand-logo" src="/images/atv-logo-crop.png" alt="Logo ATV">
            <div>Arsip Digital<small>Diskominfo Kota Batu</small></div>
        </a>
        <nav>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="sidebar-icon icon-dashboard" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 11.5 12 5l8 6.5v7a1.5 1.5 0 0 1-1.5 1.5H15v-5.5H9V20H5.5A1.5 1.5 0 0 1 4 18.5v-7Z"/></svg>
                </span>
                <span>Dashboard</span>
            </a>
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
                    @foreach($sidebarCategories as $category)
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
            <a class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}" href="{{ route('schedules.index') }}">
                <span class="sidebar-icon icon-schedule" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3a1 1 0 0 1 1 1v1h8V4a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 22 7.5v11A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-11A2.5 2.5 0 0 1 4.5 5H6V4a1 1 0 0 1 1-1Zm13 7H4v8.5a.5.5 0 0 0 .5.5h15a.5.5 0 0 0 .5-.5V10ZM5 7a1 1 0 0 0-1 1v.5h16V8a1 1 0 0 0-1-1H5Zm2.5 5h3v3h-3v-3Zm5 0h3v3h-3v-3Z"/></svg>
                </span>
                <span>Jadwal Tayang</span>
            </a>
            <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <span class="sidebar-icon icon-report" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.8V8h3.2L13 4.8ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h3v2H8V8Z"/></svg>
                </span>
                <span>Laporan</span>
            </a>
            <a class="{{ request()->routeIs('profile*') ? 'active' : '' }}" href="{{ route('profile') }}">
                <span class="sidebar-icon icon-user" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 12.2A4.2 4.2 0 1 0 12 3.8a4.2 4.2 0 0 0 0 8.4Zm0 2c-4.2 0-7.8 2.2-8.8 5.4A1.2 1.2 0 0 0 4.3 21h15.4a1.2 1.2 0 0 0 1.1-1.4c-1-3.2-4.6-5.4-8.8-5.4Z"/></svg>
                </span>
                <span>Profil</span>
            </a>
            @if(auth()->user()?->isSuperAdmin())
                <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <span class="sidebar-icon icon-user" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7.5 8.5c.8-3.1 3.8-5.5 7.5-5.5s6.7 2.4 7.5 5.5a1.2 1.2 0 0 1-1.2 1.5H5.7a1.2 1.2 0 0 1-1.2-1.5ZM19 6h2V4h1.5v2h2v1.5h-2v2H21v-2h-2V6Z"/></svg>
                    </span>
                    <span>Manajemen User</span>
                </a>
            @endif
        </nav>
        <div class="profile"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div></div>
        <form action="{{ route('logout') }}" method="post">@csrf<button class="logout">Keluar</button></form>
    </aside>
    <div class="sidebar-backdrop" onclick="document.querySelector('.sidebar')?.classList.remove('open')"></div>
    <main class="main">
        <header><button class="menu" onclick="document.querySelector('.sidebar')?.classList.toggle('open')">&#9776;</button><div><small>Sistem Manajemen Arsip</small><strong>ATV Diskominfo Kota Batu</strong></div></header>
        <div class="content">
            @if(session('success'))<div class="alert success"><strong>Berhasil</strong><span>{{ session('success') }}</span></div>@endif
            @if(session('warning'))<div class="alert warning"><strong>Perhatian</strong><span>{{ session('warning') }}</span></div>@endif
            @if($errors->any())
                <div class="alert danger">
                    <strong>Periksa kembali</strong>
                    <span>
                        @foreach($errors->all() as $message)
                            {{ $message }}{{ ! $loop->last ? ' ' : '' }}
                        @endforeach
                    </span>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<script>
(() => {
    const loadingText = (button) => {
        const text = button.textContent.trim().toLowerCase();

        if (text.includes('upload')) return 'Mengupload...';
        if (text.includes('generate') || text.includes('export')) return 'Membuat laporan...';
        if (text.includes('terapkan')) return 'Menerapkan...';
        if (text.includes('hapus')) return 'Menghapus...';
        if (text.includes('keluar')) return 'Keluar...';

        return 'Menyimpan...';
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.dataset.loadingHandled === 'true' || form.dataset.skipGlobalLoading === 'true') return;

        const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
        if (!submitter || submitter.dataset.noLoading === 'true') return;

        form.dataset.loadingHandled = 'true';
        form.classList.add('is-submitting');

        const buttons = form.querySelectorAll('button, .btn');
        buttons.forEach((button) => {
            if (button.tagName === 'BUTTON') button.disabled = true;
            button.classList.add('is-disabled');
        });

        if (submitter.tagName === 'BUTTON') {
            submitter.dataset.originalText = submitter.innerHTML;
            submitter.innerHTML = `<span class="btn-spinner" aria-hidden="true"></span><span>${loadingText(submitter)}</span>`;
            submitter.classList.add('is-loading');
        }
    }, true);
})();
</script>
</body>
</html>



