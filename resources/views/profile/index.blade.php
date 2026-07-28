@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<div class="profile-page">
    <section class="card profile-hero">
        <div class="profile-hero-main">
            <div class="profile-avatar profile-avatar-lg">{{ str($user->name)->substr(0, 1)->upper() }}</div>
            <div class="profile-title-block">
                <span class="eyebrow">Profil Pengguna</span>
                <h1>{{ $user->name }}</h1>
                <p>Identitas akun untuk mengelola arsip video ATV di Diskominfo Kota Batu.</p>
                <div class="profile-pills">
                    <span>Username</span>
                    <span>Email</span>
                    <span>Aktif</span>
                </div>
            </div>
        </div>

        <div class="profile-hero-side">
            <div class="profile-meta">
                <div>
                    <span>Username</span>
                    <strong>{{ $user->name }}</strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong>{{ $user->email }}</strong>
                </div>
            </div>
            <div class="profile-hero-actions">
                <a class="btn primary" href="{{ route('profile.edit') }}">Edit Profil</a>
                <a class="btn primary" href="{{ route('archives.index') }}">Lihat Arsip</a>
                <a class="btn" href="{{ route('reports.index') }}">Buka Laporan</a>
            </div>
        </div>
    </section>

    <div class="profile-stats">
        <div class="profile-stat card">
            <span>Total Arsip</span>
            <strong>{{ $stats['total_archives'] }}</strong>
            <small>Arsip yang dibuat akun ini</small>
        </div>
        <div class="profile-stat card navy">
            <span>Siap Tayang</span>
            <strong>{{ $stats['ready'] }}</strong>
            <small>Video menunggu jadwal</small>
        </div>
        <div class="profile-stat card green">
            <span>Sudah Tayang</span>
            <strong>{{ $stats['aired'] }}</strong>
            <small>Video yang sudah aktif</small>
        </div>
        <div class="profile-stat card orange">
            <span>Draft</span>
            <strong>{{ $stats['draft'] }}</strong>
            <small>Masih belum dipublikasikan</small>
        </div>
    </div>

    <div class="profile-grid">
        <section class="card profile-panel profile-span">
            <div class="card-head">
                <div>
                    <h2>Arsip Terakhir</h2>
                    <small>Video yang baru dikelola</small>
                </div>
            </div>
            <div class="profile-activity-list">
                @forelse($recentArchives as $archive)
                    <a class="profile-activity-item" href="{{ route('archives.show', $archive) }}">
                        <span class="profile-activity-thumb">{{ str($archive->title)->substr(0, 1)->upper() }}</span>
                        <div>
                            <strong>{{ $archive->title }}</strong>
                            <small>{{ $archive->status }} - {{ $archive->category }}</small>
                        </div>
                        <span class="badge status-{{ str($archive->status)->slug() }}">{{ $archive->status }}</span>
                    </a>
                @empty
                    <p class="empty">Belum ada arsip yang dibuat oleh akun ini.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
