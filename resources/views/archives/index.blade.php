@extends('layouts.app')
@section('title','Arsip Video')
@section('content')
@php
    $activeCategory = request('category');
    $activeIssues = collect((array) request('issue'))->filter()->values();
    $activeStatuses = collect((array) request('status'))->filter()->values();
    $activeRatings = collect((array) request('age_rating'))->filter()->values();
    $activeSort = request('sort') ?: 'latest';
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $activeFilterCount = $activeIssues->count()
        + $activeStatuses->count()
        + $activeRatings->count()
        + ($activeSort !== 'latest' ? 1 : 0)
        + ($dateFrom ? 1 : 0)
        + ($dateTo ? 1 : 0);
@endphp

<div class="archive-hero">
    <div>
        <span class="eyebrow">Media Library</span>
        <h1>Arsip Video</h1>
        <p>Kelola, cari, dan kurasi seluruh materi tayangan ATV dalam satu ruang kerja.</p>
    </div>
    <div class="archive-hero-actions">
        <a class="btn primary archive-upload" href="{{ route('archives.upload') }}">&#43; Upload Video</a>
    </div>
</div>

<section class="archive-main">
    <form id="archive-filter-form" class="archive-filter archive-filter-clean archive-preference-filter card" method="get">
        <div class="filter-fields">
            <div class="filter-row search-row">
                <input name="search" value="{{ request('search') }}" placeholder="Cari judul atau deskripsi...">
                @if($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif
                <button type="button" class="btn filter-preview-toggle" data-filter-open>
                    Pilih Preferensi
                    @if($activeFilterCount)
                        <span>{{ $activeFilterCount }}</span>
                    @endif
                </button>
                <button class="btn primary">Cari</button>
            </div>

            <div class="filter-preview-strip" aria-label="Filter aktif">
                @foreach($activeIssues as $issue)
                    <span>Issue: {{ $issue }}</span>
                @endforeach
                @foreach($activeStatuses as $status)
                    <span>Status: {{ $status }}</span>
                @endforeach
                @foreach($activeRatings as $rating)
                    <span>Usia: {{ \App\Models\VideoArchive::AGE_RATINGS[$rating] ?? $rating }}</span>
                @endforeach
                @if($activeSort !== 'latest')
                    <span>Urutan: {{ ['oldest' => 'Terlama', 'title_asc' => 'Abjad A-Z', 'title_desc' => 'Abjad Z-A'][$activeSort] ?? 'Terbaru' }}</span>
                @endif
                @if($dateFrom || $dateTo)
                    <span>Tanggal: {{ $dateFrom ?: 'awal' }} - {{ $dateTo ?: 'akhir' }}</span>
                @endif
            </div>
        </div>

        <div class="filter-sheet" data-filter-sheet hidden>
            <div class="filter-sheet-backdrop" data-filter-close></div>
            <div class="filter-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="filter-sheet-title">
                <div class="filter-sheet-head">
                    <h2 id="filter-sheet-title">Pilih Preferensi</h2>
                    <button type="button" class="filter-sheet-close" data-filter-close aria-label="Tutup filter">&times;</button>
                </div>

                <div class="filter-sheet-body">
                    <section class="filter-choice-group">
                        <h3>Issue</h3>
                        <div class="filter-chip-grid">
                            @foreach(\App\Models\VideoArchive::ISSUES as $issue)
                                <label class="filter-chip">
                                    <input type="checkbox" name="issue[]" value="{{ $issue }}" @checked($activeIssues->contains($issue))>
                                    <span>{{ $issue }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="filter-choice-group">
                        <h3>Status</h3>
                        <div class="filter-chip-grid">
                            @foreach(\App\Models\VideoArchive::STATUSES as $status)
                                <label class="filter-chip">
                                    <input type="checkbox" name="status[]" value="{{ $status }}" @checked($activeStatuses->contains($status))>
                                    <span>{{ $status }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="filter-choice-group">
                        <h3>Urutan</h3>
                        <div class="filter-chip-grid">
                            @foreach(['latest' => 'Terbaru', 'oldest' => 'Terlama', 'title_asc' => 'Abjad A-Z', 'title_desc' => 'Abjad Z-A'] as $sortValue => $sortLabel)
                                <label class="filter-chip">
                                    <input type="radio" name="sort" value="{{ $sortValue }}" @checked($activeSort === $sortValue)>
                                    <span>{{ $sortLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="filter-choice-group">
                        <h3>Rentang Tanggal Tayang</h3>
                        <div class="filter-date-grid">
                            <label>
                                <span>Dari tanggal</span>
                                <input type="date" name="date_from" value="{{ $dateFrom }}">
                            </label>
                            <label>
                                <span>Sampai tanggal</span>
                                <input type="date" name="date_to" value="{{ $dateTo }}">
                            </label>
                        </div>
                    </section>

                    <section class="filter-choice-group">
                        <h3>Rating Usia</h3>
                        <div class="filter-chip-grid">
                            @foreach(\App\Models\VideoArchive::AGE_RATINGS as $ratingCode => $ratingLabel)
                                <label class="filter-chip">
                                    <input type="checkbox" name="age_rating[]" value="{{ $ratingCode }}" @checked($activeRatings->contains($ratingCode))>
                                    <span>{{ $ratingLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="filter-sheet-actions">
                    <a class="btn reset-filter" href="{{ route('archives.index', $activeCategory ? ['category' => $activeCategory] : []) }}">Atur Ulang</a>
                    <button class="btn primary">Terapkan</button>
                </div>
            </div>
        </div>
    </form>

    <div class="archive-table card">
        <div class="archive-table-head">
            <div>
                <span class="eyebrow">{{ $activeCategory ?: 'Semua kategori' }}</span>
                <h2>Daftar Video</h2>
            </div>
            <span>{{ $archives->total() }} arsip</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Video</th>
                        <th>Kategori</th>
                        <th>Issue</th>
                        <th>Rating Usia</th>
                        <th>Status</th>
                        <th>Pengunggah</th>
                        <th>Rencana Tayang</th>
                        <th>Durasi</th>
                        <th>Ukuran</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($archives as $item)
                        <tr>
                            <td>
                                <a class="video-title" href="{{ route('archives.show',$item) }}">
                                    <span class="video-thumb">
                                        @if($item->thumbnail_path)
                                            <img src="{{ route('archives.thumbnail', $item) }}" alt="{{ $item->title }}">
                                        @else
                                            &#9654;
                                        @endif
                                    </span>
                                    <div>
                                        <strong>{{ $item->title }}</strong>
                                        <small>{{ $item->original_name ?? ($item->video_url ? 'Link video' : 'Tanpa file video') }}</small>
                                    </div>
                                </a>
                            </td>
                            <td><span class="badge category">{{ $item->category }}</span></td>
                            <td><span class="badge issue">{{ $item->issue ?? 'Belum dipilih' }}</span></td>
                            <td><span class="badge age-rating age-rating-{{ $item->age_rating ? str($item->age_rating)->lower() : 'empty' }}">{{ $item->age_rating_label }}</span></td>
                            <td><span class="badge status-{{ str($item->status)->slug() }}">{{ $item->status }}</span></td>
                            <td>{{ $item->user->name }}</td>
                            <td>{{ $item->formatted_air_schedule }}</td>
                            <td>{{ $item->formatted_duration }}</td>
                            <td>{{ $item->formatted_size }}</td>
                            <td class="actions">
                                <a href="{{ route('archives.show',$item) }}">Detail</a>
                                <a href="{{ route('archives.edit',$item) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="empty archive-empty">
                                <strong>Arsip tidak ditemukan.</strong>
                                <span>Coba ubah kategori, issue, status, atau rentang tanggal.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $archives->links('pagination::simple-default') }}
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sheet = document.querySelector('[data-filter-sheet]');
    const openButton = document.querySelector('[data-filter-open]');
    const closeButtons = document.querySelectorAll('[data-filter-close]');

    const openSheet = () => {
        if (!sheet) return;
        sheet.hidden = false;
        document.body.classList.add('filter-sheet-open');
    };

    const closeSheet = () => {
        if (!sheet) return;
        sheet.hidden = true;
        document.body.classList.remove('filter-sheet-open');
    };

    openButton?.addEventListener('click', openSheet);
    closeButtons.forEach((button) => button.addEventListener('click', closeSheet));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet && !sheet.hidden) {
            closeSheet();
        }
    });
});
</script>
@endsection


