@php($editing = isset($archive))
<div class="form-grid"><div class="card form-card"><label>Judul tayangan <b>*</b><input id="archive-title" name="title" value="{{ old('title',$archive->title ?? '') }}" required placeholder="Contoh: Berita Kota Batu Hari Ini"></label>@error('title')<div class="error">{{ $message }}</div>@enderror
<label>Deskripsi<textarea id="archive-description" name="description" rows="6" placeholder="Tuliskan ringkasan isi video...">{{ old('description',$archive->description ?? '') }}</textarea></label>@error('description')<div class="error">{{ $message }}</div>@enderror
<div class="columns"><label>Kategori <b>*</b><select id="archive-category" name="category" required><option value="">Ditentukan otomatis</option>@foreach(\App\Models\VideoArchive::CATEGORIES as $c)<option value="{{ $c }}" @selected(old('category',$archive->category ?? '')===$c)>{{ $c }}</option>@endforeach</select><small id="category-result" class="help">Isi judul dan deskripsi; kategori akan dipilih otomatis.</small></label><label>Status <b>*</b><select name="status" required>@foreach(\App\Models\VideoArchive::STATUSES as $s)<option value="{{ $s }}" @selected(old('status',$archive->status ?? 'Draft')===$s)>{{ $s }}</option>@endforeach</select></label></div>
<label>Rencana tanggal tayang<input type="date" name="air_date" value="{{ old('air_date',isset($archive) ? $archive->air_date?->format('Y-m-d') : '') }}"></label></div>
<aside class="card form-card"><label>File video {{ $editing ? '(opsional)' : '*' }}<input class="file" type="file" name="video" accept="video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm" {{ $editing ? '' : 'required' }}></label><p class="help">Format: MP4, MPEG, MOV, AVI, atau WebM. Maksimal 500 MB.</p>@if($editing)<div class="current-file"><strong>File saat ini</strong><span>{{ $archive->original_name }}</span><small>{{ $archive->formatted_size }}</small></div>@endif @error('video')<div class="error">{{ $message }}</div>@enderror</aside></div>
<div class="form-actions"><a class="btn" href="{{ $editing ? route('archives.show',$archive) : route('archives.index') }}">Batal</a><button class="btn primary">{{ $editing ? 'Simpan Perubahan' : 'Upload dan Simpan' }}</button></div>
<script>
(() => {
    const title = document.getElementById('archive-title');
    const description = document.getElementById('archive-description');
    const category = document.getElementById('archive-category');
    const result = document.getElementById('category-result');
    let timer;

    async function detect() {
        if (title.value.trim().length < 3) return;
        result.textContent = 'Menganalisis kategori...';
        try {
            const response = await fetch(@json(route('archives.detect-category')), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                body: JSON.stringify({title: title.value, description: description.value})
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            category.value = data.category;
            result.textContent = `Otomatis: ${data.category} · keyakinan ${data.confidence}. ${data.reason}`;
        } catch (_) {
            result.textContent = 'Kategori otomatis gagal. Silakan pilih secara manual.';
        }
    }

    function schedule() { clearTimeout(timer); timer = setTimeout(detect, 650); }
    title.addEventListener('input', schedule);
    description.addEventListener('input', schedule);
})();
</script>
