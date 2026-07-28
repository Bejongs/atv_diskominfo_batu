@extends('layouts.app')
@section('title', 'Laporan')
@section('content')
@php
    $activeCategories = collect($filters['category_value']);
    $activeIssues = collect($filters['issue_value']);
    $activeStatuses = collect($filters['status_value']);
    $activeRatings = collect($filters['age_rating_value']);
    $dateFrom = $filters['date_from'];
    $dateTo = $filters['date_to'];
    $activeFilterCount = $activeCategories->count()
        + $activeIssues->count()
        + $activeStatuses->count()
        + $activeRatings->count()
        + ($dateFrom ? 1 : 0)
        + ($dateTo ? 1 : 0);
@endphp
<div class="report-hero report-hero-analytics">
    <div>
        <span class="eyebrow">Report Center</span>
        <h1>Laporan Arsip Video</h1>
        <p>Statistik, ringkasan, dan export arsip video dalam satu halaman.</p>
    </div>
    <div class="report-format-card">
        <strong>Output</strong>
        <span>Excel .xlsx</span>
        <span>PDF .pdf</span>
    </div>
</div>

<div class="report-metrics">
    <div class="report-metric">
        <span>Total arsip</span>
        <strong>{{ $stats['total_arsip'] }}</strong>
        <small>Semua data terfilter</small>
    </div>
    <div class="report-metric blue">
        <span>Sudah tayang</span>
        <strong>{{ $stats['total_sudah_tayang'] }}</strong>
        <small>Konten yang sudah live</small>
    </div>
    <div class="report-metric green">
        <span>Tanpa file</span>
        <strong>{{ $stats['total_without_file'] }}</strong>
        <small>Hanya link atau metadata</small>
    </div>
    <div class="report-metric orange">
        <span>Durasi rata-rata</span>
        <strong>{{ $stats['average_duration'] }}</strong>
        <small>Berdasarkan file yang ada</small>
    </div>
</div>

<div class="report-grid report-filter-grid">
    <aside class="card report-block report-aside">
        <div class="report-section-head compact report-aside-head">
            <div>
                <span class="eyebrow">Filter</span>
                <h2>Parameter Laporan</h2>
            </div>
        </div>
        <form method="get" action="{{ route('reports.index') }}" class="report-filter-form report-preference-filter">
            <div class="report-filter-summary">
                <div class="filter-preview-strip report-preview-strip" aria-label="Filter laporan aktif">
                    @foreach($activeCategories as $category)
                        <span>Kategori: {{ $category }}</span>
                    @endforeach
                    @foreach($activeIssues as $issue)
                        <span>Issue: {{ $issue }}</span>
                    @endforeach
                    @foreach($activeStatuses as $status)
                        <span>Status: {{ $status }}</span>
                    @endforeach
                    @foreach($activeRatings as $rating)
                        <span>Usia: {{ \App\Models\VideoArchive::AGE_RATINGS[$rating] ?? $rating }}</span>
                    @endforeach
                    @if($dateFrom || $dateTo)
                        <span>Tanggal: {{ $dateFrom ?: 'awal' }} - {{ $dateTo ?: 'akhir' }}</span>
                    @endif
                </div>
            </div>

            <div class="filter-sheet" data-filter-sheet hidden>
                <div class="filter-sheet-backdrop" data-filter-close></div>
                <div class="filter-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="report-filter-title">
                    <div class="filter-sheet-head">
                        <h2 id="report-filter-title">Pilih Preferensi</h2>
                        <button type="button" class="filter-sheet-close" data-filter-close aria-label="Tutup filter">&times;</button>
                    </div>

                    <div class="filter-sheet-body">
                        <section class="filter-choice-group">
                            <h3>Kategori</h3>
                            <div class="filter-chip-grid">
                                @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                                    <label class="filter-chip">
                                        <input type="checkbox" name="category[]" value="{{ $category }}" @checked($activeCategories->contains($category))>
                                        <span>{{ $category }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </section>

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
                    </div>

                    <div class="filter-sheet-actions">
                        <a class="btn reset-filter" href="{{ route('reports.index') }}">Atur Ulang</a>
                        <button class="btn primary">Terapkan</button>
                    </div>
                </div>
            </div>
            </form>

        <form method="post" action="{{ route('reports.export') }}" class="report-action-form" enctype="multipart/form-data">
            @csrf
            @foreach($activeCategories as $category)
                <input type="hidden" name="category[]" value="{{ $category }}">
            @endforeach
            @foreach($activeIssues as $issue)
                <input type="hidden" name="issue[]" value="{{ $issue }}">
            @endforeach
            @foreach($activeStatuses as $status)
                <input type="hidden" name="status[]" value="{{ $status }}">
            @endforeach
            @foreach($activeRatings as $rating)
                <input type="hidden" name="age_rating[]" value="{{ $rating }}">
            @endforeach
            <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
            <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
            <div class="report-title-filter-row">
                <label>Judul laporan
                    <input name="title" value="{{ old('title', 'Laporan Arsip Video ATV') }}" required>
                </label>
                <button type="button" class="btn filter-preview-toggle" data-filter-open>
                    Pilih Preferensi
                    @if($activeFilterCount)
                        <span>{{ $activeFilterCount }}</span>
                    @endif
                </button>
            </div>
            <div class="report-export-row">
                <label>Format export
                    <select name="format" required>
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                </label>
                <button class="btn primary">Generate</button>
            </div>
        </form>
    </aside>
</div>

<div class="report-grid report-grid-wide">
    <section class="card report-block">
        <div class="card-head">
            <div>
                <h2>Rating Usia</h2>
                <small>Penyesuaian penonton video</small>
            </div>
        </div>
        <div class="rating-list">
            @foreach($ageRatingBreakdown as $item)
                <div class="rating-row">
                    <span class="badge age-rating age-rating-{{ $item['code'] ? strtolower($item['code']) : 'empty' }}">{{ $item['label'] }}</span>
                    <strong>{{ $item['count'] }}</strong>
                    <small>{{ $item['percent'] }}%</small>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card report-block">
        <div class="card-head">
            <div>
                <h2>Distribusi Status</h2>
                <small>Workflow arsip video</small>
            </div>
        </div>
        <div class="status-chart">
            @foreach($statusBreakdown as $item)
                <div class="status-chart-row">
                    <div>
                        <strong>{{ $item['label'] }}</strong>
                        <span>{{ $item['count'] }} video</span>
                    </div>
                    <div class="chart-track">
                        <i class="status-{{ str($item['label'])->slug() }}" style="width: {{ $item['percent'] }}%"></i>
                    </div>
                    <b>{{ $item['percent'] }}%</b>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="report-grid report-grid-wide">
    <section class="card report-block">
        <div class="card-head">
            <div>
                <h2>Distribusi Kategori</h2>
                <small>Komposisi kategori arsip aktif</small>
            </div>
        </div>
        <div class="report-bars">
            @foreach($categoryBreakdown as $item)
                <div class="report-bar-row">
                    <div>
                        <strong>{{ $item['label'] }}</strong>
                        <span>{{ $item['count'] }} arsip</span>
                    </div>
                    <div class="chart-track">
                        <i class="status-siap-tayang" style="width: {{ $item['percent'] }}%"></i>
                    </div>
                    <b>{{ $item['percent'] }}%</b>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card report-block">
        <div class="card-head">
            <div>
                <h2>Durasi & File</h2>
                <small>Informasi teknis arsip yang terfilter</small>
            </div>
        </div>
        <div class="technical-grid">
            <div>
                <span>Total durasi</span>
                <strong>{{ $stats['total_duration'] }}</strong>
            </div>
            <div>
                <span>Rata-rata ukuran file</span>
                <strong>{{ $stats['average_size'] }}</strong>
            </div>
            <div>
                <span>File tersedia</span>
                <strong>{{ $stats['file_ready'] }}</strong>
            </div>
            <div>
                <span>Tanpa file</span>
                <strong>{{ $stats['total_without_file'] }}</strong>
            </div>
        </div>
    </section>
</div>

<section class="card report-block">
    <div class="card-head">
        <div>
            <h2>Daftar Arsip</h2>
            <small>20 data terbaru dari hasil filter</small>
        </div>
    </div>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Durasi</th>
                    <th>Tayang</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentArchives as $archive)
                    <tr>
                        <td>{{ $archive->title }}</td>
                        <td>{{ $archive->category }}</td>
                        <td><span class="badge status-{{ str($archive->status)->slug() }}">{{ $archive->status }}</span></td>
                        <td><span class="badge age-rating age-rating-{{ $archive->age_rating ? strtolower($archive->age_rating) : 'empty' }}">{{ $archive->age_rating_label }}</span></td>
                        <td>{{ $archive->formatted_duration }}</td>
                        <td>{{ $archive->formatted_air_schedule }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">Belum ada arsip untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
