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
    $canDeleteArchives = auth()->user()?->canDeleteArchives();
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

    <form id="archive-bulk-form" method="post" action="{{ route('archives.bulk-action') }}" data-skip-global-loading="true">
        @csrf
        <input type="hidden" name="bulk_action" value="" data-bulk-action>
        <input type="hidden" name="confirmed_delete" value="0" data-confirmed-delete>
        <div class="archive-selection-bar" data-selection-bar hidden>
            <label class="archive-select-all">
                <input type="checkbox" aria-label="Pilih semua arsip" data-selection-select-all>
                <span>Pilih semua</span>
            </label>
            <strong><span data-selected-count>0</span> dipilih</strong>
            <select name="status" data-bulk-status>
                <option value="">Ubah status ke...</option>
                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
            <button class="btn primary" type="submit" name="action" value="change_status" data-status-action>Ubah Status</button>
            @if($canDeleteArchives)
                <button class="btn danger compact-danger" type="button" data-delete-selected>Hapus</button>
            @endif
        </div>
        <div data-selection-hidden></div>

        <div class="archive-table card">
            <div class="archive-table-head">
                <div>
                    <span class="eyebrow">{{ $activeCategory ?: 'Semua kategori' }}</span>
                    <h2>Daftar Video</h2>
                </div>
                <span>{{ $archives->total() }} arsip</span>
            </div>

            <div class="table-wrap">
                <table class="archive-table-grid">
                    <thead>
                        <tr>
                            <th><input type="checkbox" aria-label="Pilih semua arsip" data-table-select-all></th>
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
                                <td data-label="Pilih">
                                    <input type="checkbox" value="{{ $item->id }}" data-bulk-item>
                                </td>
                                <td data-label="Video">
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
                                <td data-label="Kategori"><span class="badge category">{{ $item->category }}</span></td>
                                <td data-label="Issue"><span class="badge issue">{{ $item->issue ?? 'Belum dipilih' }}</span></td>
                                <td data-label="Rating Usia"><span class="badge age-rating age-rating-{{ $item->age_rating ? str($item->age_rating)->lower() : 'empty' }}">{{ $item->age_rating_label }}</span></td>
                                <td data-label="Status"><span class="badge status-{{ str($item->status)->slug() }}">{{ $item->status }}</span></td>
                                <td data-label="Pengunggah">{{ $item->user->name }}</td>
                                <td data-label="Rencana Tayang">{{ $item->formatted_air_schedule }}</td>
                                <td data-label="Durasi">{{ $item->formatted_duration }}</td>
                                <td data-label="Ukuran">{{ $item->formatted_size }}</td>
                                <td class="actions" data-label="Aksi">
                                    <a href="{{ route('archives.show',$item) }}">Detail</a>
                                    <a href="{{ route('archives.edit',$item) }}">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="empty archive-empty">
                                    <strong>Arsip tidak ditemukan.</strong>
                                    <span>Coba ubah kategori, issue, status, atau rentang tanggal.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="archive-mobile-list">
            @forelse($archives as $item)
                <article class="archive-mobile-card">
                    <label class="archive-mobile-check">
                        <input type="checkbox" value="{{ $item->id }}" data-bulk-item>
                    </label>
                    <a class="archive-mobile-video" href="{{ route('archives.show', $item) }}">
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
                    <div class="archive-mobile-badges">
                        <span class="badge category">{{ $item->category }}</span>
                        <span class="badge issue">{{ $item->issue ?? 'Belum dipilih' }}</span>
                        <span class="badge age-rating age-rating-{{ $item->age_rating ? str($item->age_rating)->lower() : 'empty' }}">{{ $item->age_rating_label }}</span>
                        <span class="badge status-{{ str($item->status)->slug() }}">{{ $item->status }}</span>
                    </div>
                    <div class="archive-mobile-meta">
                        <span>{{ $item->formatted_air_schedule }}</span>
                        <span>{{ $item->user->name }}</span>
                    </div>
                    <div class="archive-mobile-actions">
                        <a class="btn" href="{{ route('archives.show',$item) }}">Detail</a>
                        <a class="btn primary" href="{{ route('archives.edit',$item) }}">Edit</a>
                    </div>
                </article>
            @empty
                <div class="archive-mobile-empty card">
                    <strong>Arsip tidak ditemukan.</strong>
                    <span>Coba ubah kategori, issue, status, atau rentang tanggal.</span>
                </div>
            @endforelse
        </div>
    </form>

    {{ $archives->links('pagination.simple-atv') }}
</section>

@if($canDeleteArchives)
    <div id="bulk-delete-modal" class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="bulk-delete-title" hidden>
        <div class="delete-confirm-dialog">
            <div class="delete-confirm-icon" aria-hidden="true">!</div>
            <div>
                <h2 id="bulk-delete-title">Hapus arsip terpilih?</h2>
                <p><strong><span data-bulk-delete-count>0</span> arsip</strong> akan dihapus bersama file video yang tersimpan. Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <div class="delete-confirm-actions">
                <button type="button" class="btn" data-bulk-delete-close>Batal</button>
                <button type="submit" class="btn danger" form="archive-bulk-form" name="bulk_action" value="delete" data-bulk-delete-confirm>Hapus Arsip</button>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sheet = document.querySelector('[data-filter-sheet]');
    const openButton = document.querySelector('[data-filter-open]');
    const closeButtons = document.querySelectorAll('[data-filter-close]');
    const bulkForm = document.getElementById('archive-bulk-form');
    const selectionBar = document.querySelector('[data-selection-bar]');
    const selectedCount = document.querySelector('[data-selected-count]');
    const tableSelectAll = document.querySelector('[data-table-select-all]');
    const selectionSelectAll = document.querySelector('[data-selection-select-all]');
    const bulkItems = document.querySelectorAll('[data-bulk-item]');
    const bulkStatus = document.querySelector('[data-bulk-status]');
    const bulkActionInput = document.querySelector('[data-bulk-action]');
    const confirmedDeleteInput = document.querySelector('[data-confirmed-delete]');
    const hiddenSelection = document.querySelector('[data-selection-hidden]');
    const bulkDeleteModal = document.getElementById('bulk-delete-modal');
    const bulkDeleteCount = document.querySelector('[data-bulk-delete-count]');
    const bulkDeleteConfirm = document.querySelector('[data-bulk-delete-confirm]');
    const bulkDeleteCloseButtons = document.querySelectorAll('[data-bulk-delete-close]');
    const selectionKey = 'atv.archive.selected';
    const shouldClearStoredSelection = @json($errors->has('selected'));

    if (shouldClearStoredSelection) {
        localStorage.removeItem(selectionKey);
    }

    const getStoredSelection = () => {
        try {
            return new Set(JSON.parse(localStorage.getItem(selectionKey) || '[]').map(String));
        } catch {
            return new Set();
        }
    };

    const storeSelection = (selected) => {
        localStorage.setItem(selectionKey, JSON.stringify(Array.from(selected)));
    };

    const renderHiddenSelection = (selected) => {
        if (!hiddenSelection) return;
        hiddenSelection.innerHTML = '';
        selected.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected[]';
            input.value = id;
            hiddenSelection.appendChild(input);
        });
    };

    const syncTableSelectAll = () => {
        const selected = getStoredSelection();
        bulkItems.forEach((item) => {
            item.checked = selected.has(String(item.value));
        });

        if (!tableSelectAll) return;
        const items = Array.from(bulkItems);
        const uniqueCurrentIds = Array.from(new Set(items.map((item) => String(item.value))));
        const checkedOnPage = uniqueCurrentIds.filter((id) => selected.has(id)).length;
        tableSelectAll.checked = uniqueCurrentIds.length > 0 && checkedOnPage === uniqueCurrentIds.length;
        tableSelectAll.indeterminate = checkedOnPage > 0 && checkedOnPage < uniqueCurrentIds.length;
        if (selectionSelectAll) {
            selectionSelectAll.checked = tableSelectAll.checked;
            selectionSelectAll.indeterminate = tableSelectAll.indeterminate;
        }
        if (selectedCount) selectedCount.textContent = selected.size;
        if (selectionBar) selectionBar.hidden = selected.size === 0;
        renderHiddenSelection(selected);
    };

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

    const openBulkDeleteModal = (count) => {
        if (!bulkDeleteModal) return;
        if (bulkDeleteCount) bulkDeleteCount.textContent = count;
        bulkDeleteModal.hidden = false;
        document.body.classList.add('delete-confirm-open');
    };

    const closeBulkDeleteModal = () => {
        if (!bulkDeleteModal) return;
        bulkDeleteModal.hidden = true;
        document.body.classList.remove('delete-confirm-open');
        if (confirmedDeleteInput) confirmedDeleteInput.value = '0';
    };

    openButton?.addEventListener('click', openSheet);
    closeButtons.forEach((button) => button.addEventListener('click', closeSheet));
    tableSelectAll?.addEventListener('change', () => {
        const selected = getStoredSelection();
        const checked = tableSelectAll.checked;
        bulkItems.forEach((item) => {
            if (checked) {
                selected.add(String(item.value));
            } else {
                selected.delete(String(item.value));
            }
        });
        storeSelection(selected);
        syncTableSelectAll();
    });
    selectionSelectAll?.addEventListener('change', () => {
        const selected = getStoredSelection();
        const checked = selectionSelectAll.checked;
        bulkItems.forEach((item) => {
            if (checked) {
                selected.add(String(item.value));
            } else {
                selected.delete(String(item.value));
            }
        });
        storeSelection(selected);
        syncTableSelectAll();
    });
    bulkItems.forEach((item) => item.addEventListener('change', () => {
        const selected = getStoredSelection();
        if (item.checked) {
            selected.add(String(item.value));
        } else {
            selected.delete(String(item.value));
        }
        storeSelection(selected);
        syncTableSelectAll();
    }));

    document.querySelector('[data-status-action]')?.addEventListener('click', () => {
        if (bulkActionInput) bulkActionInput.value = 'change_status';
    });

    document.querySelector('[data-delete-selected]')?.addEventListener('click', () => {
        const selected = getStoredSelection();

        if (!selected.size) {
            syncTableSelectAll();

            return;
        }

        if (bulkActionInput) bulkActionInput.value = 'delete';
        if (confirmedDeleteInput) confirmedDeleteInput.value = '0';
        renderHiddenSelection(selected);
        openBulkDeleteModal(selected.size);
    });

    bulkDeleteCloseButtons.forEach((button) => button.addEventListener('click', closeBulkDeleteModal));
    bulkDeleteModal?.addEventListener('click', (event) => {
        if (event.target === bulkDeleteModal) closeBulkDeleteModal();
    });
    bulkDeleteConfirm?.addEventListener('click', (event) => {
        const selected = getStoredSelection();
        if (!selected.size) {
            event.preventDefault();
            closeBulkDeleteModal();
            syncTableSelectAll();

            return;
        }

        if (bulkActionInput) bulkActionInput.value = 'delete';
        if (confirmedDeleteInput) confirmedDeleteInput.value = '1';
        renderHiddenSelection(selected);
    });

    bulkForm?.addEventListener('submit', (event) => {
        const submitter = event.submitter;
        const action = submitter?.value || bulkActionInput?.value || (bulkStatus?.value ? 'change_status' : '');
        const selected = getStoredSelection();
        const checked = selected.size;

        if (bulkActionInput) {
            bulkActionInput.value = action;
        }

        if (!checked) {
            event.preventDefault();
            return;
        }

        if (action === 'change_status' && !bulkStatus?.value) {
            event.preventDefault();
            bulkStatus?.focus();
            return;
        }

        if (action === 'delete' && confirmedDeleteInput?.value !== '1') {
            event.preventDefault();
            openBulkDeleteModal(checked);

            return;
        }

        renderHiddenSelection(selected);
        localStorage.removeItem(selectionKey);
    });
    syncTableSelectAll();
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet && !sheet.hidden) {
            closeSheet();
        }

        if (event.key === 'Escape' && bulkDeleteModal && !bulkDeleteModal.hidden) {
            closeBulkDeleteModal();
        }
    });
});
</script>
@endsection


