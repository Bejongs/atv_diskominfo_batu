@extends('layouts.app')
@section('title',$archive->title)
@section('content')
@php
    $workflow = ['Draft', 'Review', 'Siap Tayang', 'Sudah Tayang', 'Diarsipkan'];
@endphp

<div class="page-head">
    <div>
        <a class="back" href="{{ route('archives.index') }}">&lt;- Kembali ke arsip</a>
        <h1>{{ $archive->title }}</h1>
        <p>{{ $archive->category }} - {{ $archive->issue ?? 'Belum ada issue' }} - Diunggah {{ $archive->created_at->format('d M Y, H:i') }}</p>
    </div>
    <div>
        <a class="btn" href="{{ route('archives.edit',$archive) }}">Edit</a>
        @if($archive->file_path)
            <a class="btn primary" href="{{ route('archives.download',$archive) }}">Unduh</a>
        @endif
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
        @if($archive->file_path)
            <video controls preload="metadata">
                <source src="{{ route('archives.preview', $archive) }}" type="{{ $archive->mime_type }}">
                Browser tidak mendukung video.
            </video>
        @else
            <div class="empty">Belum ada file video.</div>
        @endif
        <div class="video-links">
            @if($archive->file_path)
                <a class="video-fallback-link btn" href="{{ route('archives.preview', $archive) }}" target="_blank" rel="noopener">Buka preview video</a>
            @endif
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
            <dt>Rating usia</dt>
            <dd><span class="badge age-rating age-rating-{{ $archive->age_rating ? str($archive->age_rating)->lower() : 'empty' }}">{{ $archive->age_rating_label }}</span></dd>
            <dt>Rencana tayang</dt>
            <dd>{{ $archive->formatted_air_schedule }}</dd>
            <dt>Durasi</dt>
            <dd>{{ $archive->formatted_duration }}</dd>
            <dt>Link video</dt>
            <dd>
                @if($archive->video_url)
                    <a class="metadata-link" href="{{ $archive->video_url }}" target="_blank" rel="noopener noreferrer">{{ $archive->video_url }}</a>
                @else
                    Belum ada link
                @endif
            </dd>
            <dt>Nama file</dt>
            <dd>{{ $archive->original_name ?? 'Belum ada file' }}</dd>
            <dt>Ukuran</dt>
            <dd>{{ $archive->formatted_size }}</dd>
            <dt>Pengunggah</dt>
            <dd>{{ $archive->user->name }}</dd>
        </dl>
    </aside>
</div>

<section class="card description description-card">
    <div class="description-head">
        <h2>Deskripsi</h2>
    </div>
    <div class="description-body">
        <p>{{ $archive->description ?: 'Tidak ada deskripsi.' }}</p>
    </div>
</section>

<section class="card activity-card">
    <div class="card-head">
        <h2>Riwayat Aktivitas</h2>
    </div>
    @forelse($archive->activities()->with('user')->latest()->limit(5)->get() as $activity)
        <div class="activity-row">
            <strong>{{ ucfirst($activity->action) }}</strong>
            @php($actorName = $activity->meta['actor'] ?? $activity->user->name)
            <small>{{ $actorName }} - {{ $activity->created_at->diffForHumans() }}</small>
        </div>
    @empty
        <p class="empty">Belum ada riwayat aktivitas.</p>
    @endforelse
</section>

<form id="delete-archive-form" method="post" action="{{ route('archives.destroy',$archive) }}">
    @csrf
    @method('delete')
    <button class="btn danger" type="button" data-delete-open>Hapus Arsip</button>
</form>

<div id="delete-archive-modal" class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="delete-archive-title" hidden>
    <div class="delete-confirm-dialog">
        <div class="delete-confirm-icon" aria-hidden="true">!</div>
        <div>
            <h2 id="delete-archive-title">Hapus Arsip?</h2>
            <p>Arsip <strong>{{ $archive->title }}</strong> akan dihapus bersama file video yang tersimpan. Tindakan ini tidak bisa dibatalkan.</p>
        </div>
        <div class="delete-confirm-actions">
            <button type="button" class="btn" data-delete-close>Batal</button>
            <button type="submit" class="btn danger" form="delete-archive-form">Hapus Arsip</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-archive-modal');
    const openButton = document.querySelector('[data-delete-open]');
    const closeButtons = document.querySelectorAll('[data-delete-close]');

    const openModal = () => {
        if (!modal) return;
        modal.hidden = false;
        document.body.classList.add('delete-confirm-open');
    };

    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('delete-confirm-open');
    };

    openButton?.addEventListener('click', openModal);
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
});
</script>
@endsection


