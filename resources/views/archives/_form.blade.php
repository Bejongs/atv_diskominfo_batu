@php
    $editing = isset($archive);
    $durationTotal = (int) old('duration_seconds', $archive->duration_seconds ?? (($archive->duration_minutes ?? 0) * 60));
    $durationHours = old('duration_hours', $durationTotal > 0 ? intdiv($durationTotal, 3600) : '');
    $durationMinutePart = old('duration_minute_part', $durationTotal > 0 ? intdiv($durationTotal % 3600, 60) : '');
    $durationSecondPart = old('duration_second_part', $durationTotal > 0 ? $durationTotal % 60 : '');
    $airTime = old('air_time', isset($archive) && $archive->air_time ? substr((string) $archive->air_time, 0, 5) : '');
@endphp
<div id="video-navigation-warning" class="alert warning upload-navigation-warning" role="alert" hidden>
    Video sudah dipilih. Selesaikan upload dengan tombol "Upload dan Simpan" sebelum pindah ke menu lain.
</div>

<div id="video-navigation-modal" class="upload-warning-modal" role="dialog" aria-modal="true" aria-labelledby="video-navigation-modal-title" hidden>
    <div class="upload-warning-dialog">
        <div class="upload-warning-icon" aria-hidden="true">!</div>
        <div>
            <h2 id="video-navigation-modal-title">Upload belum disimpan</h2>
            <p>Video sudah dipilih. Simpan upload terlebih dahulu sebelum pindah ke menu lain agar file tidak hilang.</p>
        </div>
        <div class="upload-warning-actions">
            <button type="button" class="btn primary" id="video-navigation-modal-ok">Tetap di halaman</button>
        </div>
    </div>
</div>

<div class="card upload-composer">
    <div class="upload-composer-head">
        <div>
            <span class="eyebrow">Media Upload</span>
            <h1>{{ $editing ? 'Edit Arsip' : 'Upload Video' }}</h1>
            <p>{{ $editing ? 'Perbarui informasi dan file video arsip.' : 'Tambahkan materi tayangan baru ke arsip.' }}</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-card">
            <div class="upload-card-head">
            <div>
                <h2>Informasi Video</h2>
                <small>Lengkapi metadata arsip tayangan</small>
            </div>
        </div>
        <label>Judul tayangan <b>*</b>
            <input id="archive-title" name="title" value="{{ old('title',$archive->title ?? '') }}" required placeholder="Contoh: Berita Kota Batu Hari Ini">
        </label>
        @error('title')<div class="error">{{ $message }}</div>@enderror

        <label>Deskripsi
            <textarea id="archive-description" name="description" rows="6" placeholder="Tuliskan ringkasan isi video...">{{ old('description',$archive->description ?? '') }}</textarea>
        </label>
        @error('description')<div class="error">{{ $message }}</div>@enderror

        <div class="columns">
            <label>Kategori <b>*</b>
                <select id="archive-category" name="category" required>
                    <option value="">Ditentukan otomatis</option>
                    @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                        <option value="{{ $category }}" @selected(old('category',$archive->category ?? '')===$category)>{{ $category }}</option>
                    @endforeach
                </select>
                <small id="category-result" class="help">Isi judul dan deskripsi; kategori akan dipilih otomatis.</small>
            </label>

            <label>Issue <b>*</b>
                <select id="archive-issue" name="issue" required>
                    <option value="">Pilih issue</option>
                    @foreach(\App\Models\VideoArchive::ISSUES as $issue)
                        <option value="{{ $issue }}" @selected(old('issue',$archive->issue ?? '')===$issue)>{{ $issue }}</option>
                    @endforeach
                </select>
                <small id="issue-result" class="help">Issue juga akan dipilih otomatis dari judul dan deskripsi.</small>
            </label>
        </div>

        <label>Rating usia
            <select name="age_rating">
                <option value="">Pilih rating usia</option>
                @foreach(\App\Models\VideoArchive::AGE_RATINGS as $ratingCode => $ratingLabel)
                    <option value="{{ $ratingCode }}" @selected(old('age_rating',$archive->age_rating ?? '')===$ratingCode)>{{ $ratingLabel }}</option>
                @endforeach
            </select>
            <small class="help">Pilih batas usia penonton yang sesuai untuk materi video.</small>
        </label>
        @error('age_rating')<div class="error">{{ $message }}</div>@enderror

        <label>Status <b>*</b>
            <select name="status" required>
                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status',$archive->status ?? 'Draft')===$status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>

        <label>Rencana tayang
            <div class="air-schedule-fields">
                <div>
                    <input type="date" name="air_date" value="{{ old('air_date',isset($archive) ? $archive->air_date?->format('Y-m-d') : '') }}" aria-label="Tanggal tayang">
                    <small>Tanggal</small>
                </div>
                <div>
                    <input type="time" name="air_time" value="{{ $airTime }}" step="60" aria-label="Jam tayang">
                    <small>Jam</small>
                </div>
            </div>
            <small class="help air-schedule-help">Isi jam dan menit agar jadwal upload/tayang lebih jelas.</small>
        </label>
        @error('air_date')<div class="error">{{ $message }}</div>@enderror
        @error('air_time')<div class="error">{{ $message }}</div>@enderror

        <label>Durasi video
            <div class="duration-fields">
                <div>
                    <input id="duration-hours" type="number" name="duration_hours" value="{{ $durationHours }}" min="0" max="999" placeholder="0">
                    <small>Jam</small>
                </div>
                <div>
                    <input id="duration-minutes" type="number" name="duration_minute_part" value="{{ $durationMinutePart }}" min="0" max="59" placeholder="0">
                    <small>Menit</small>
                </div>
                <div>
                    <input id="duration-seconds" type="number" name="duration_second_part" value="{{ $durationSecondPart }}" min="0" max="59" placeholder="0">
                    <small>Detik</small>
                </div>
            </div>
            <small class="help">Durasi akan terisi otomatis setelah video dipilih. Tetap bisa diedit manual jika perlu.</small>
        </label>
        @error('duration_hours')<div class="error">{{ $message }}</div>@enderror
        @error('duration_minute_part')<div class="error">{{ $message }}</div>@enderror
        @error('duration_second_part')<div class="error">{{ $message }}</div>@enderror

        <label>Link video
            <input type="url" name="video_url" value="{{ old('video_url',$archive->video_url ?? '') }}" placeholder="https://youtube.com/watch?v=...">
            <small class="help">Opsional. Isi kalau video juga punya link publik, YouTube, Drive, atau portal lain.</small>
        </label>
        @error('video_url')<div class="error">{{ $message }}</div>@enderror
        </div>

        <aside class="form-card">
            <div class="upload-card-head">
            <div>
                <h2>File Video</h2>
                <small>Unggah file atau simpan metadata saja</small>
            </div>
        </div>
        <label>File video (opsional)</label>
        <div id="video-drop-zone" class="drop-zone" tabindex="0" role="button" aria-label="Pilih atau jatuhkan file video">
            <input id="archive-video-file" class="file-input" type="file" name="{{ $editing ? 'video' : 'video[]' }}" accept="video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm" {{ $editing ? '' : 'multiple' }}>
            <div class="drop-zone-content">
                <strong>Tarik video ke sini</strong>
                <span>atau klik untuk memilih file</span>
                <small>MP4, MPEG, MOV, AVI, WebM. Maksimal 500 MB.</small>
            </div>
        </div>
        <p class="help">Opsional. Saat upload baru, kamu bisa drag and drop beberapa file sekaligus.</p>
        <div id="video-upload-preview" class="upload-preview" hidden>
            <strong>Preview file dipilih</strong>
            <div class="preview-list"></div>
        </div>
        <div id="duration-hidden-fields"></div>
        @if($editing && $archive->file_path)
            <div class="current-file">
                <strong>File saat ini</strong>
                <span>{{ $archive->original_name }}</span>
                <small>{{ $archive->formatted_size }}</small>
            </div>
        @endif
        @error('video')<div class="error">{{ $message }}</div>@enderror
        </aside>
    </div>

        <div class="form-actions form-actions-inline">
            <a class="btn" data-cancel-upload href="{{ $editing ? route('archives.show',$archive) : route('archives.index') }}">Batal</a>
            <button class="btn primary">{{ $editing ? 'Simpan Perubahan' : 'Upload dan Simpan' }}</button>
        </div>
    </div>
</div>

<script>
(() => {
    const title = document.getElementById('archive-title');
    const description = document.getElementById('archive-description');
    const category = document.getElementById('archive-category');
    const issue = document.getElementById('archive-issue');
    const categoryResult = document.getElementById('category-result');
    const issueResult = document.getElementById('issue-result');
    const videoInput = document.getElementById('archive-video-file');
    const dropZone = document.getElementById('video-drop-zone');
    const durationHoursInput = document.getElementById('duration-hours');
    const durationMinutesInput = document.getElementById('duration-minutes');
    const durationSecondsInput = document.getElementById('duration-seconds');
    const durationHiddenFields = document.getElementById('duration-hidden-fields');
    const previewWrap = document.getElementById('video-upload-preview');
    const previewList = previewWrap?.querySelector('.preview-list');
    const navigationWarning = document.getElementById('video-navigation-warning');
    const navigationModal = document.getElementById('video-navigation-modal');
    const navigationModalOk = document.getElementById('video-navigation-modal-ok');
    const archiveForm = videoInput?.closest('form');
    const blockedNavigationMessage = 'Video sudah dipilih. Upload dan simpan video terlebih dahulu sebelum pindah ke menu lain.';
    let timer;
    let previewUrls = [];
    let isSubmitting = false;
    let pendingVideoSelected = (videoInput?.files?.length || 0) > 0;

    function hasSelectedVideo() {
        return pendingVideoSelected && (videoInput?.files?.length || 0) > 0;
    }

    function showNavigationWarning() {
        if (navigationWarning) {
            navigationWarning.classList.remove('success');
            navigationWarning.classList.add('warning');
            navigationWarning.textContent = blockedNavigationMessage;
            navigationWarning.hidden = false;
            navigationWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (navigationModal) {
            navigationModal.hidden = false;
            navigationModalOk?.focus();
        }
    }

    function closeNavigationWarning() {
        if (navigationModal) navigationModal.hidden = true;
    }

    function isBlockedAppNavigation(link) {
        if (!link?.href) return false;

        const target = new URL(link.href, window.location.href);
        const current = new URL(window.location.href);
        const isSamePageAnchor = target.pathname === current.pathname && target.search === current.search && target.hash;

        return target.origin === current.origin && !isSamePageAnchor;
    }

    async function detect() {
        if (title.value.trim().length < 3) return;

        categoryResult.textContent = 'Menganalisis kategori...';
        issueResult.textContent = 'Menganalisis issue...';

        try {
            const response = await fetch(@json(route('archives.detect-category')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({ title: title.value, description: description.value }),
            });

            if (!response.ok) throw new Error();

            const data = await response.json();
            category.value = data.category;
            issue.value = data.issue;
            categoryResult.textContent = `Otomatis: ${data.category} - keyakinan ${data.confidence}. ${data.reason}`;
            issueResult.textContent = `Otomatis: ${data.issue} - keyakinan ${data.issue_confidence}. ${data.issue_reason}`;
        } catch (_) {
            categoryResult.textContent = 'Kategori otomatis gagal. Silakan pilih secara manual.';
            issueResult.textContent = 'Issue otomatis gagal. Silakan pilih secara manual.';
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(detect, 650);
    }

    title.addEventListener('input', schedule);
    description.addEventListener('input', schedule);

    function clearPreviews() {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
        if (previewList) previewList.innerHTML = '';
        if (durationHiddenFields) durationHiddenFields.innerHTML = '';
        if (previewWrap) previewWrap.hidden = true;
        if (navigationWarning) navigationWarning.hidden = true;
    }

    function clearSelectedVideo() {
        pendingVideoSelected = false;
        if (videoInput) videoInput.value = '';
        clearPreviews();
        closeNavigationWarning();
    }

    function showCancelFeedback() {
        if (!navigationWarning) return;

        navigationWarning.classList.remove('warning');
        navigationWarning.classList.add('success');
        navigationWarning.textContent = 'Upload dibatalkan. Video yang dipilih sudah dihapus.';
        navigationWarning.hidden = false;
        navigationWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function formatDuration(totalSeconds) {
        if (!totalSeconds) return 'Durasi sedang dibaca...';

        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        const parts = [];

        if (hours > 0) parts.push(`${hours} jam`);
        if (minutes > 0) parts.push(`${minutes} menit`);
        if (seconds > 0 || !parts.length) parts.push(`${seconds} detik`);

        return parts.join(' ');
    }

    function setMainDuration(totalSeconds) {
        if (!durationHoursInput || !durationMinutesInput || !durationSecondsInput || !totalSeconds) return;

        durationHoursInput.value = Math.floor(totalSeconds / 3600);
        durationMinutesInput.value = Math.floor((totalSeconds % 3600) / 60);
        durationSecondsInput.value = totalSeconds % 60;
    }

    function createDurationInput(index) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'duration_seconds_per_file[]';
        input.dataset.index = index;
        durationHiddenFields?.appendChild(input);

        return input;
    }

    function updatePreviews() {
        clearPreviews();
        const files = Array.from(videoInput.files || []);
        pendingVideoSelected = files.length > 0;

        if (!files.length || !previewList || !previewWrap) return;

        files.forEach((file) => {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);

            const item = document.createElement('div');
            item.className = 'preview-item';
            const video = document.createElement('video');
            video.controls = true;
            video.preload = 'metadata';
            video.src = url;

            const meta = document.createElement('div');
            const name = document.createElement('strong');
            const size = document.createElement('small');
            const duration = document.createElement('small');
            const durationInput = createDurationInput(previewList.children.length);
            name.textContent = file.name;
            size.textContent = `${(file.size / 1024 / 1024).toFixed(1)} MB`;
            duration.textContent = 'Durasi sedang dibaca...';
            meta.append(name, size, duration);
            item.append(video, meta);
            previewList.appendChild(item);

            video.addEventListener('loadedmetadata', () => {
                if (!Number.isFinite(video.duration)) {
                    duration.textContent = 'Durasi belum terbaca';

                    return;
                }

                const totalSeconds = Math.max(1, Math.round(video.duration));
                durationInput.value = totalSeconds;
                duration.textContent = formatDuration(totalSeconds);

                if (previewList.children.length === 1 || durationHiddenFields?.querySelector('input') === durationInput) {
                    setMainDuration(totalSeconds);
                }
            });
        });

        previewWrap.hidden = false;
    }

    function assignFiles(files) {
        if (!videoInput || !files.length) return;

        const transfer = new DataTransfer();
        files.forEach((file) => transfer.items.add(file));
        videoInput.files = transfer.files;
        updatePreviews();
    }

    videoInput?.addEventListener('change', updatePreviews);
    if (!hasSelectedVideo()) {
        clearPreviews();
        closeNavigationWarning();
    }

    archiveForm?.addEventListener('submit', () => {
        isSubmitting = true;
        pendingVideoSelected = false;
    });

    navigationModalOk?.addEventListener('click', closeNavigationWarning);
    navigationModal?.addEventListener('click', (event) => {
        if (event.target === navigationModal) closeNavigationWarning();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeNavigationWarning();
    });

    document.addEventListener('click', (event) => {
        const cancelLink = event.target.closest('[data-cancel-upload]');
        if (cancelLink) {
            if (!hasSelectedVideo()) return;

            event.preventDefault();
            clearSelectedVideo();
            showCancelFeedback();
            window.setTimeout(() => {
                window.location.href = cancelLink.href;
            }, 550);

            return;
        }

        if (!hasSelectedVideo()) return;

        const link = event.target.closest('a');

        if (!isBlockedAppNavigation(link)) return;

        event.preventDefault();
        showNavigationWarning();
    }, true);

    dropZone?.addEventListener('click', (event) => {
        if (event.target === videoInput) return;

        videoInput?.click();
    });
    dropZone?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            videoInput?.click();
        }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropZone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.add('dragging');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropZone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.remove('dragging');
        });
    });

    dropZone?.addEventListener('drop', (event) => {
        assignFiles(Array.from(event.dataTransfer?.files || []).filter((file) => !file.type || file.type.startsWith('video/')));
    });

    window.addEventListener('beforeunload', (event) => {
        if (!hasSelectedVideo() || isSubmitting) return;

        event.preventDefault();
        event.returnValue = blockedNavigationMessage;
    });

    window.addEventListener('pagehide', clearPreviews);
})();
</script>
