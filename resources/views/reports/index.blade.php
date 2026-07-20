@extends('layouts.app')
@section('title','Laporan')
@section('content')
<div class="report-hero">
    <div>
        <span class="eyebrow">Report Builder</span>
        <h1>Laporan Arsip Video</h1>
        <p>Buat template laporan sendiri, gunakan placeholder otomatis, lalu export sebagai Excel atau PDF.</p>
    </div>
    <div class="report-format-card">
        <strong>Output tersedia</strong>
        <span>Excel .xlsx</span>
        <span>PDF .pdf</span>
    </div>
</div>

<form class="report-builder" method="post" action="{{ route('reports.export') }}" enctype="multipart/form-data">
    @csrf
    <section class="card report-panel">
        <div class="report-section-head">
            <div>
                <span class="eyebrow">Template</span>
                <h2>Isi Template Laporan</h2>
            </div>
            <small>Placeholder akan diganti otomatis saat export.</small>
        </div>

        <label>Judul laporan
            <input name="title" value="{{ old('title', 'Laporan Arsip Video ATV') }}" required>
        </label>

        <label>File template
            <input type="file" name="template_file" id="template-file" accept=".txt,.md,.html,.htm,.rtf,.csv,.json,.xml,.log,.php,.blade.php,.docx,.pdf" required>
            <small class="help">Upload file template. Kalau isinya teks, akan dipakai sebagai template laporan.</small>
        </label>
        @error('template_file')<div class="error">{{ $message }}</div>@enderror

        <input type="hidden" name="template_text" id="template-text" value="{{ old('template_text', $defaultTemplate) }}">

        <div class="template-preview-wrap">
            <div class="template-preview-head">
                <strong>Preview template</strong>
                <small>Hasil baca file</small>
            </div>
            <pre id="template-preview">{{ old('template_text', $defaultTemplate) }}</pre>
        </div>
    </section>

    <aside class="card report-sidebar">
        <div class="report-section-head compact">
            <div>
                <span class="eyebrow">Pengaturan</span>
                <h2>Filter & Format</h2>
            </div>
        </div>

        <label>Format export
            <select name="format" required>
                <option value="xlsx">Excel (.xlsx)</option>
                <option value="pdf">PDF (.pdf)</option>
            </select>
        </label>

        <label>Kategori
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>
        </label>

        <label>Issue
            <select name="issue">
                <option value="">Semua issue</option>
                @foreach(\App\Models\VideoArchive::ISSUES as $issue)
                    <option value="{{ $issue }}">{{ $issue }}</option>
                @endforeach
            </select>
        </label>

        <label>Status
            <select name="status">
                <option value="">Semua status</option>
                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </label>

        <div class="report-dates">
            <label>Dari tanggal
                <input type="date" name="date_from">
            </label>
            <label>Sampai tanggal
                <input type="date" name="date_to">
            </label>
        </div>

        <button class="btn primary full">Generate Laporan</button>

        <div class="placeholder-card">
            <strong>Placeholder</strong>
            <code>@{{tanggal}}</code>
            <code>@{{periode}}</code>
            <code>@{{total_arsip}}</code>
            <code>@{{total_news}}</code>
            <code>@{{total_iklan_layanan_masyarakat}}</code>
            <code>@{{total_program}}</code>
            <code>@{{total_siap_tayang}}</code>
            <code>@{{total_sudah_tayang}}</code>
            <code>@{{daftar_video}}</code>
        </div>
    </aside>
</form>

<script>
(() => {
    const fileInput = document.getElementById('template-file');
    const preview = document.getElementById('template-preview');
    const templateText = document.getElementById('template-text');

    fileInput?.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) return;

        try {
            const text = await file.text();
            const content = text.trim() || 'Template kosong.';
            preview.textContent = content;
            templateText.value = content;
        } catch (_) {
            preview.textContent = 'Gagal membaca isi file template. Gunakan file teks jika ingin isi dipakai langsung.';
            templateText.value = preview.textContent;
        }
    });
})();
</script>
@endsection
