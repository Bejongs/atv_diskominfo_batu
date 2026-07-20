@extends('layouts.app')
@section('title',$archive->title)
@section('content')
@php
    $workflow = ['Draft', 'Review', 'Siap Tayang', 'Sudah Tayang', 'Diarsipkan'];
@endphp

<div class="page-head">
    <div>
        <a class="back" href="{{ route('archives.index') }}">← Kembali ke arsip</a>
        <h1>{{ $archive->title }}</h1>
        <p>{{ $archive->category }} - {{ $archive->issue ?? 'Belum ada issue' }} - Diunggah {{ $archive->created_at->format('d M Y, H:i') }}</p>
    </div>
    <div>
        <a class="btn" href="{{ route('archives.edit',$archive) }}">Edit</a>
        <a class="btn primary" href="{{ route('archives.download',$archive) }}">Unduh</a>
    </div>
</div>

<section class="workflow card">
    @foreach($workflow as $step)
        <div class="workflow-step {{ $step === $archive->status ? 'active' : '' }} {{ array_search($step, $workflow) < array_search($archive->status, $workflow) ? 'done' : '' }}">
            <span></span>
            <strong>{{ $step }}</strong>
        </div>
    @endforeach
</section>

<div class="detail-grid">
    <section class="card video-card">
        <video controls preload="metadata">
            <source src="{{ route('archives.preview', $archive) }}" type="{{ $archive->mime_type }}">
            Browser tidak mendukung video.
        </video>
        <div class="video-links">
            <a class="video-fallback-link btn" href="{{ route('archives.preview', $archive) }}" target="_blank" rel="noopener">Buka preview video</a>
            @if($archive->video_url)
                <a class="video-source-link" href="{{ $archive->video_url }}" target="_blank" rel="noopener noreferrer">Buka link video</a>
            @endif
        </div>
    </section>
    <aside class="card metadata">
        <h2>Informasi Video</h2>
        <dl>
            <dt>Status</dt>
            <dd><span class="badge status-{{ str($archive->status)->slug() }}">{{ $archive->status }}</span></dd>
            <dt>Kategori</dt>
            <dd>{{ $archive->category }}</dd>
            <dt>Issue</dt>
            <dd>{{ $archive->issue ?? 'Belum dipilih' }}</dd>
            <dt>Rencana tayang</dt>
            <dd>{{ $archive->air_date?->format('d F Y') ?? 'Belum ditentukan' }}</dd>
            <dt>Link video</dt>
            <dd>
                @if($archive->video_url)
                    <a class="metadata-link" href="{{ $archive->video_url }}" target="_blank" rel="noopener noreferrer">{{ $archive->video_url }}</a>
                @else
                    Belum ada link
                @endif
            </dd>
            <dt>Nama file</dt>
            <dd>{{ $archive->original_name }}</dd>
            <dt>Ukuran</dt>
            <dd>{{ $archive->formatted_size }}</dd>
            <dt>Pengunggah</dt>
            <dd>{{ $archive->user->name }}</dd>
        </dl>
    </aside>
</div>

<section class="card description">
    <h2>Deskripsi</h2>
    <p>{{ $archive->description ?: 'Tidak ada deskripsi.' }}</p>
</section>

<section class="card activity-card">
    <div class="card-head">
        <h2>Riwayat Aktivitas</h2>
    </div>
    @forelse($archive->activities()->with('user')->latest()->limit(5)->get() as $activity)
        <div class="activity-row">
            <strong>{{ ucfirst($activity->action) }}</strong>
            @php($actorName = $activity->meta['actor'] ?? $activity->user->name)
            <small>{{ $actorName }} &middot; {{ $activity->created_at->diffForHumans() }}</small>
        </div>
    @empty
        <p class="empty">Belum ada riwayat aktivitas.</p>
    @endforelse
</section>

<form method="post" action="{{ route('archives.destroy',$archive) }}" onsubmit="return confirm('Yakin ingin menghapus arsip dan file video ini?')">
    @csrf
    @method('delete')
    <button class="btn danger">Hapus Arsip</button>
</form>
@endsection


