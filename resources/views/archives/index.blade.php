@extends('layouts.app')
@section('title','Arsip Video')
@section('content')
@php
    $activeCategory = request('category');
    $activeIssue = request('issue');
@endphp

<div class="archive-hero">
    <div>
        <span class="eyebrow">Media Library</span>
        <h1>Arsip Video</h1>
        <p>Kelola, cari, dan kurasi seluruh materi tayangan ATV dalam satu ruang kerja.</p>
    </div>
    <div class="archive-hero-actions">
        <details class="export-menu">
            <summary class="btn">Export Data</summary>
            <div>
                <a href="{{ route('archives.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">Excel (.xlsx)</a>
                <a href="{{ route('archives.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">PDF (.pdf)</a>
            </div>
        </details>
        <a class="btn primary archive-upload" href="{{ route('archives.upload') }}">&#43; Upload Video</a>
    </div>
</div>

<section class="archive-main">
    <form class="archive-filter archive-filter-clean card" method="get">
        <div class="filter-fields">
            <div class="filter-row search-row">
                <input name="search" value="{{ request('search') }}" placeholder="Cari judul atau deskripsi...">
                <button class="btn primary">Filter</button>
            </div>

            <div class="filter-row option-row">
                @if($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif

                <select name="issue">
                    <option value="">Semua issue</option>
                    @foreach(\App\Models\VideoArchive::ISSUES as $issue)
                        <option @selected($activeIssue === $issue)>{{ $issue }}</option>
                    @endforeach
                </select>

                <select name="status">
                    <option value="">Semua status</option>
                    @foreach(\App\Models\VideoArchive::STATUSES as $status)
                        <option @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>

                <select name="sort">
                    <option value="">Terbaru</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option>
                    <option value="title" @selected(request('sort') === 'title')>Judul A-Z</option>
                </select>

                <div class="date-range">
                    <label>
                        <span>Dari</span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Tanggal mulai">
                    </label>
                    <i></i>
                    <label>
                        <span>Sampai</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Tanggal akhir">
                    </label>
                </div>

                <a class="btn reset-filter" href="{{ route('archives.index') }}">Reset</a>
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
                        <th>Status</th>
                        <th>Pengunggah</th>
                        <th>Rencana Tayang</th>
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
                                        <small>{{ $item->original_name }}</small>
                                    </div>
                                </a>
                            </td>
                            <td><span class="badge category">{{ $item->category }}</span></td>
                            <td><span class="badge issue">{{ $item->issue ?? 'Belum dipilih' }}</span></td>
                            <td><span class="badge status-{{ str($item->status)->slug() }}">{{ $item->status }}</span></td>
                            <td>{{ $item->user->name }}</td>
                            <td>{{ $item->air_date?->format('d M Y') ?? '&mdash;' }}</td>
                            <td>{{ $item->formatted_size }}</td>
                            <td class="actions">
                                <a href="{{ route('archives.show',$item) }}">Detail</a>
                                <a href="{{ route('archives.edit',$item) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty archive-empty">
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
@endsection


