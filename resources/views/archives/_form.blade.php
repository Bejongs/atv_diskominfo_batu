@php($editing = isset($archive))
<div class="form-grid">
    <div class="card form-card">
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

        <label>Status <b>*</b>
            <select name="status" required>
                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status',$archive->status ?? 'Draft')===$status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>

        <label>Rencana tanggal tayang
            <input type="date" name="air_date" value="{{ old('air_date',isset($archive) ? $archive->air_date?->format('Y-m-d') : '') }}">
        </label>

        <label>Link video
            <input type="url" name="video_url" value="{{ old('video_url',$archive->video_url ?? '') }}" placeholder="https://youtube.com/watch?v=...">
            <small class="help">Opsional. Isi kalau video juga punya link publik, YouTube, Drive, atau portal lain.</small>
        </label>
        @error('video_url')<div class="error">{{ $message }}</div>@enderror
    </div>

    <aside class="card form-card">
        <label>File video {{ $editing ? '(opsional)' : '*' }}
            <input id="archive-video-file" class="file" type="file" name="{{ $editing ? 'video' : 'video[]' }}" accept="video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm" {{ $editing ? '' : 'multiple required' }}>
        </label>
        <p class="help">Format: MP4, MPEG, MOV, AVI, atau WebM. Maksimal 500 MB. Saat upload baru, kamu bisa pilih beberapa file sekaligus.</p>
        <div id="video-upload-preview" class="upload-preview" hidden>
            <strong>Preview file dipilih</strong>
            <div class="preview-list"></div>
        </div>
        @if($editing)
            <div class="current-file">
                <strong>File saat ini</strong>
                <span>{{ $archive->original_name }}</span>
                <small>{{ $archive->formatted_size }}</small>
            </div>
        @endif
        @error('video')<div class="error">{{ $message }}</div>@enderror
    </aside>
</div>

<div class="form-actions">
    <a class="btn" href="{{ $editing ? route('archives.show',$archive) : route('archives.index') }}">Batal</a>
    <button class="btn primary">{{ $editing ? 'Simpan Perubahan' : 'Upload dan Simpan' }}</button>
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
    const previewWrap = document.getElementById('video-upload-preview');
    const previewList = previewWrap?.querySelector('.preview-list');
    let timer;
    let previewUrls = [];

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
            categoryResult.textContent = `Otomatis: ${data.category} · keyakinan ${data.confidence}. ${data.reason}`;
            issueResult.textContent = `Otomatis: ${data.issue} · keyakinan ${data.issue_confidence}. ${data.issue_reason}`;
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
        if (previewWrap) previewWrap.hidden = true;
    }

    videoInput?.addEventListener('change', () => {
        clearPreviews();
        const files = Array.from(videoInput.files || []);
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
            name.textContent = file.name;
            size.textContent = `${(file.size / 1024 / 1024).toFixed(1)} MB`;
            meta.append(name, size);
            item.append(video, meta);
            previewList.appendChild(item);
        });

        previewWrap.hidden = false;
    });

    window.addEventListener('beforeunload', clearPreviews);
})();
</script>
