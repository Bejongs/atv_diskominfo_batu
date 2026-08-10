<?php

namespace App\Http\Controllers;

use App\Models\VideoArchiveActivity;
use App\Models\VideoArchive;
use App\Services\CategoryDetector;
use App\Services\VideoArchiveStatusSyncer;
use App\Services\YouTubeFeedImporter;
use App\Support\SimplePdfExporter;
use App\Support\SimpleXlsxExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoArchiveController extends Controller
{
    public function detectCategory(Request $request, CategoryDetector $detector): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        return response()->json($detector->detect($data['title'], $data['description'] ?? null));
    }

    public function syncYouTube(Request $request, YouTubeFeedImporter $importer)
    {
        if (! $importer->isConfigured()) {
            return redirect()
                ->route('archives.index')
                ->with('warning', 'Isi YOUTUBE_CHANNEL_ID atau YOUTUBE_FEED_URL terlebih dahulu sebelum sinkron YouTube.');
        }

        $result = $importer->import($request->user()->id);

        return redirect()
            ->route('archives.index')
            ->with('success', 'Sinkron YouTube selesai. '.$result['created'].' video baru ditambahkan, '.$result['updated'].' durasi diperbarui, '.$result['skipped'].' video sudah ada.');
    }

    public function index(Request $request, VideoArchiveStatusSyncer $syncer)
    {
        $syncer->syncDueToAired();

        $archives = $this->archiveQuery($request)->paginate(10)->withQueryString();

        return view('archives.index', compact('archives'));
    }

    public function create() { return view('archives.create'); }

    public function store(Request $request)
    {
        $data = $this->validated($request, false, true);
        $files = $request->file('video', []);
        $createdCount = 0;

        DB::transaction(function () use ($request, $data, $files, &$createdCount): void {
            if ($files === []) {
                $archiveData = $data;
                $archiveData['user_id'] = $request->user()->id;
                $archiveData['thumbnail_path'] = $this->storeThumbnail($archiveData['title'], $archiveData['category'], $archiveData['issue']);
                unset($archiveData['video'], $archiveData['duration_hours'], $archiveData['duration_minute_part'], $archiveData['duration_second_part'], $archiveData['duration_seconds_per_file']);

                $archive = VideoArchive::create($archiveData);
                $this->logActivity($archive, $request->user()->id, 'created', [
                    'original_name' => null,
                    'bulk' => false,
                    'snapshot' => $this->snapshotArchive($archive),
                ]);
                $createdCount++;

                return;
            }

            foreach ($files as $index => $file) {
                $archiveData = $data;
                $archiveData['user_id'] = $request->user()->id;
                $archiveData['title'] = $this->titleForFile($data['title'], $file->getClientOriginalName(), count($files) > 1, $index + 1);
                $archiveData['file_path'] = $file->store('videos', 'public');
                $archiveData['thumbnail_path'] = $this->storeThumbnail($archiveData['title'], $archiveData['category'], $archiveData['issue']);
                $archiveData['original_name'] = $file->getClientOriginalName();
                $archiveData['mime_type'] = $file->getMimeType();
                $archiveData['file_size'] = $file->getSize();
                $archiveData['duration_seconds'] = $data['duration_seconds_per_file'][$index] ?? $data['duration_seconds'];
                $archiveData['duration_minutes'] = $archiveData['duration_seconds'] ? (int) ceil($archiveData['duration_seconds'] / 60) : null;
                unset($archiveData['video'], $archiveData['duration_hours'], $archiveData['duration_minute_part'], $archiveData['duration_second_part'], $archiveData['duration_seconds_per_file']);

                $archive = VideoArchive::create($archiveData);
                $this->logActivity($archive, $request->user()->id, 'created', [
                    'original_name' => $file->getClientOriginalName(),
                    'bulk' => count($files) > 1,
                    'snapshot' => $this->snapshotArchive($archive),
                ]);
                $createdCount++;
            }
        });

        $redirect = redirect()->route('archives.index')->with('success', $createdCount > 1 ? $createdCount.' video berhasil ditambahkan ke arsip.' : 'Video berhasil ditambahkan ke arsip.');

        if ($warning = $this->scheduleConflictWarning($data)) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function show(VideoArchive $archive) { return view('archives.show', compact('archive')); }
    public function edit(VideoArchive $archive) { return view('archives.edit', compact('archive')); }

    public function bulkAction(Request $request)
    {
        if (! $request->filled('action') && $request->filled('bulk_action')) {
            $request->merge(['action' => $request->input('bulk_action')]);
        }

        if (! $request->filled('action') && $request->filled('status')) {
            $request->merge(['action' => 'change_status']);
        }

        if (! $request->filled('action')) {
            return redirect()->route('archives.index');
        }

        $data = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'distinct'],
            'action' => ['required', Rule::in(['change_status', 'delete'])],
            'status' => ['nullable', 'required_if:action,change_status', Rule::in(VideoArchive::STATUSES)],
        ], [
            'selected.required' => 'Pilih minimal satu arsip terlebih dahulu.',
            'selected.min' => 'Pilih minimal satu arsip terlebih dahulu.',
            'selected.*.integer' => 'Pilihan arsip tidak valid. Muat ulang halaman lalu pilih ulang.',
            'selected.*.distinct' => 'Ada pilihan arsip yang terduplikasi. Muat ulang halaman lalu pilih ulang.',
            'action.required' => 'Pilih aksi yang ingin dijalankan.',
            'action.in' => 'Aksi yang dipilih tidak valid.',
            'status.required_if' => 'Pilih status tujuan terlebih dahulu.',
            'status.in' => 'Status tujuan tidak valid.',
        ]);

        if ($data['action'] === 'delete') {
            Gate::authorize('deleteAny', VideoArchive::class);
        }

        $archives = VideoArchive::whereIn('id', $data['selected'])->get();

        if ($archives->isEmpty()) {
            return back()->withErrors([
                'selected' => 'Arsip yang dipilih sudah tidak tersedia. Muat ulang halaman lalu pilih ulang.',
            ])->withInput();
        }

        DB::transaction(function () use ($archives, $data, $request): void {
            foreach ($archives as $archive) {
                if ($data['action'] === 'delete') {
                    if ($archive->file_path) {
                        Storage::disk('public')->delete($archive->file_path);
                    }

                    if ($archive->thumbnail_path) {
                        Storage::disk('public')->delete($archive->thumbnail_path);
                    }

                    $this->logActivity($archive, $request->user()->id, 'deleted', [
                        'snapshot' => $this->snapshotArchive($archive),
                    ]);
                    $archive->delete();

                    continue;
                }

                $before = $this->snapshotArchive($archive);
                $archive->update(['status' => $data['status']]);
                $this->logActivity($archive, $request->user()->id, 'updated', [
                    'changes' => $this->buildChangeSet($before, $this->snapshotArchive($archive)),
                ]);
            }
        });

        $count = $archives->count();

        if ($data['action'] === 'delete') {
            return redirect()->route('archives.index')->with('success', $count.' arsip berhasil dihapus.');
        }

        return redirect()->route('archives.index')->with('success', $count.' arsip berhasil diubah ke status '.$data['status'].'.');
    }

    public function update(Request $request, VideoArchive $archive)
    {
        $data = $this->validated($request, false, false);
        $before = $this->snapshotArchive($archive);
        if ($request->hasFile('video')) {
            Storage::disk('public')->delete($archive->file_path);
            Storage::disk('public')->delete($archive->thumbnail_path);
            $file = $request->file('video');
            $data += [
                'file_path' => $file->store('videos', 'public'),
                'thumbnail_path' => $this->storeThumbnail($data['title'], $data['category'], $data['issue']),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        }
        unset($data['video'], $data['duration_hours'], $data['duration_minute_part'], $data['duration_second_part'], $data['duration_seconds_per_file']);
        $archive->update($data);
        $this->logActivity($archive, $request->user()->id, 'updated', [
            'changes' => $this->buildChangeSet($before, $this->snapshotArchive($archive)),
        ]);
        $redirect = redirect()->route('archives.show', $archive)->with('success', 'Data arsip berhasil diperbarui.');

        if ($warning = $this->scheduleConflictWarning($data, $archive)) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function destroy(VideoArchive $archive)
    {
        Gate::authorize('delete', $archive);

        if ($archive->file_path) {
            Storage::disk('public')->delete($archive->file_path);
        }

        if ($archive->thumbnail_path) {
            Storage::disk('public')->delete($archive->thumbnail_path);
        }
        $this->logActivity($archive, auth()->id(), 'deleted', [
            'snapshot' => $this->snapshotArchive($archive),
        ]);
        $archive->delete();
        return redirect()->route('archives.index')->with('success', 'Arsip berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'xlsx');
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);

        $filename = 'arsip-video-'.now()->format('Ymd_His').'.'.$format;
        $columns = ['Judul', 'Kategori', 'Issue', 'Rating Usia', 'Status', 'Durasi', 'Rencana Tayang', 'Link Video', 'Pengunggah', 'Nama File', 'Ukuran', 'Dibuat'];
        $rows = $this->exportRows($request);

        if ($format === 'pdf') {
            return response(SimplePdfExporter::make('Data Arsip Video ATV', $columns, $rows), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response(SimpleXlsxExporter::make('Data Arsip Video ATV', $columns, $rows), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function exportRows(Request $request): array
    {
        return $this->archiveQuery($request)->get()->map(fn (VideoArchive $archive) => [
            $archive->title,
            $archive->category,
            $archive->issue,
            $archive->age_rating_label,
            $archive->status,
            $archive->formatted_duration,
            $archive->formatted_air_schedule,
            $archive->video_url,
            $archive->user?->name,
            $archive->original_name,
            $archive->formatted_size,
            $archive->created_at?->format('Y-m-d H:i:s'),
        ])->all();
    }

    public function download(VideoArchive $archive)
    {
        abort_unless($archive->file_path, 404);
        abort_unless(Storage::disk('public')->exists($archive->file_path), 404);
        return Storage::disk('public')->download($archive->file_path, $archive->original_name);
    }

    public function preview(VideoArchive $archive)
    {
        abort_unless($archive->file_path, 404);
        abort_unless(Storage::disk('public')->exists($archive->file_path), 404);

        return response()->file(Storage::disk('public')->path($archive->file_path), [
            'Content-Type' => $archive->mime_type ?: Storage::disk('public')->mimeType($archive->file_path) ?: 'video/mp4',
            'Content-Disposition' => 'inline; filename="'.$archive->original_name.'"',
        ]);
    }

    public function thumbnail(Request $request, VideoArchive $archive)
    {
        return response($this->buildThumbnailSvg(
            $archive->title,
            $archive->category,
            $archive->issue ?? 'Video',
            $request->boolean('preview')
        ), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    private function validated(Request $request, bool $required, bool $bulk): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(VideoArchive::CATEGORIES)],
            'issue' => ['required', Rule::in(VideoArchive::ISSUES)],
            'age_rating' => ['nullable', Rule::in(array_keys(VideoArchive::AGE_RATINGS))],
            'status' => ['required', Rule::in(VideoArchive::STATUSES)],
            'air_date' => ['nullable', 'date'],
            'air_time' => ['nullable', 'date_format:H:i'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:999'],
            'duration_minute_part' => ['nullable', 'integer', 'min:0', 'max:59'],
            'duration_second_part' => ['nullable', 'integer', 'min:0', 'max:59'],
            'duration_seconds_per_file' => ['nullable', 'array'],
            'duration_seconds_per_file.*' => ['nullable', 'integer', 'min:1', 'max:3599999'],
        ];

        if ($bulk) {
            $rules['video'] = ['nullable', 'array'];
            $rules['video.*'] = ['file', 'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm', 'max:512000'];
        } else {
            $rules['video'] = [$required ? 'required' : 'nullable', 'file', 'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm', 'max:512000'];
        }

        $data = $request->validate($rules, [
            'video.max' => 'Ukuran video maksimal 500 MB.',
            'video.mimetypes' => 'File harus berupa video MP4, MPEG, MOV, AVI, atau WebM.',
            'video.*.max' => 'Ukuran video maksimal 500 MB.',
            'video.*.mimetypes' => 'Setiap file harus berupa video MP4, MPEG, MOV, AVI, atau WebM.',
            'duration_hours.max' => 'Durasi jam terlalu besar.',
            'duration_minute_part.max' => 'Durasi menit harus 0 sampai 59.',
            'duration_second_part.max' => 'Durasi detik harus 0 sampai 59.',
        ]);

        $hours = (int) ($data['duration_hours'] ?? 0);
        $minutes = (int) ($data['duration_minute_part'] ?? 0);
        $seconds = (int) ($data['duration_second_part'] ?? 0);
        $data['duration_seconds'] = ($hours + $minutes + $seconds) > 0 ? ($hours * 3600) + ($minutes * 60) + $seconds : null;
        $data['duration_minutes'] = $data['duration_seconds'] ? (int) ceil($data['duration_seconds'] / 60) : null;

        return $data;
    }

    private function archiveQuery(Request $request)
    {
        $issues = collect((array) $request->input('issue'))->filter()->values();
        $statuses = collect((array) $request->input('status'))->filter()->values();
        $ageRatings = collect((array) $request->input('age_rating'))->filter()->values();

        return VideoArchive::with('user')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', '%'.$request->search.'%')
                ->orWhere('description', 'like', '%'.$request->search.'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($issues->isNotEmpty(), fn ($q) => $q->whereIn('issue', $issues))
            ->when($statuses->isNotEmpty(), fn ($q) => $q->whereIn('status', $statuses))
            ->when($ageRatings->isNotEmpty(), fn ($q) => $q->whereIn('age_rating', $ageRatings))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('air_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('air_date', '<=', $request->date_to))
            ->when($request->filled('sort'), function ($q) use ($request): void {
                if ($request->sort === 'oldest') {
                    $q->orderBy('created_at');

                    return;
                }

                if ($request->sort === 'title_asc') {
                    $q->orderBy('title');

                    return;
                }

                if ($request->sort === 'title_desc') {
                    $q->orderByDesc('title');

                    return;
                }

                $q->latest();
            }, fn ($q) => $q->latest());
    }

    private function titleForFile(string $title, string $originalName, bool $bulk, int $index): string
    {
        if (! $bulk) {
            return $title;
        }

        $name = pathinfo($originalName, PATHINFO_FILENAME);

        return trim($title.' - '.$name.' #'.$index);
    }

    private function storeThumbnail(string $title, string $category, string $issue): ?string
    {
        $path = 'thumbnails/'.Str::uuid().'.svg';
        $thumbnail = $this->buildThumbnailSvg($title, $category, $issue);
        Storage::disk('public')->put($path, $thumbnail);

        return $path;
    }

    private function buildThumbnailSvg(string $title, string $category, string $issue, bool $previewMode = false): string
    {
        $safeTitle = e(Str::limit($title, 52));
        $safeCategory = e($category);
        $safeIssue = e($issue);
        $theme = match ($category) {
            'Iklan Layanan Masyarakat' => ['#0d4d42', '#18a36a', '#24d8c6', '#dffcf4'],
            'Program' => ['#4f1f0d', '#f59e0b', '#ff7a59', '#fff1de'],
            default => ['#0b1f4a', '#1b4aa0', '#19b5c8', '#dbeafe'],
        };
        [$colorStart, $colorMid, $colorAccent, $colorSoft] = $theme;

        if ($previewMode) {
            return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" fill="none">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="$colorStart"/>
      <stop offset="55%" stop-color="$colorMid"/>
      <stop offset="100%" stop-color="$colorAccent"/>
    </linearGradient>
  </defs>
  <rect width="640" height="360" rx="28" fill="url(#bg)"/>
  <text x="56" y="148" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="15" font-weight="700" letter-spacing="1">PREVIEW</text>
  <text x="56" y="206" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="34" font-weight="800">$safeTitle</text>
  <text x="56" y="250" fill="#f8fbfb" font-family="Inter, Arial, sans-serif" font-size="21" font-weight="500">$safeCategory &#183; $safeIssue</text>
</svg>
SVG;
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" fill="none">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="$colorStart"/>
      <stop offset="55%" stop-color="$colorMid"/>
      <stop offset="100%" stop-color="$colorAccent"/>
    </linearGradient>
    <linearGradient id="frame" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="100%" stop-color="$colorSoft"/>
    </linearGradient>
  </defs>
  <rect width="640" height="360" rx="28" fill="url(#bg)"/>
  <rect x="28" y="28" width="584" height="304" rx="24" fill="none" stroke="url(#frame)" stroke-width="2" stroke-dasharray="10 8" opacity=".9"/>
  <rect x="44" y="44" width="552" height="272" rx="20" fill="#ffffff10" stroke="#ffffff26" stroke-width="1.5"/>
  <rect x="62" y="60" width="118" height="38" rx="12" fill="#ffffff1a"/>
  <text x="86" y="86" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="22" font-weight="800">ATV</text>
  <circle cx="542" cy="86" r="72" fill="#ffffff12"/>
  <circle cx="102" cy="286" r="96" fill="#ffffff0b"/>
  <path d="M512 114l32 22-32 22z" fill="#ffffff18"/>
  <rect x="54" y="140" width="196" height="7" rx="3.5" fill="#ffffff30"/>
  <text x="56" y="156" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="1">PREVIEW</text>
  <text x="56" y="206" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="34" font-weight="800">$safeTitle</text>
  <text x="56" y="248" fill="#f8fbff" font-family="Inter, Arial, sans-serif" font-size="21" font-weight="500">$safeCategory &#183; $safeIssue</text>
  <rect x="472" y="132" width="116" height="96" rx="18" fill="#00000012" stroke="#ffffff26" stroke-width="1.5"/>
  <rect x="488" y="148" width="84" height="64" rx="14" fill="#ffffff1a"/>
  <path d="M520 173L500 161V185L520 173Z" fill="#ffffff"/>
  <rect x="470" y="240" width="122" height="10" rx="5" fill="#ffffff24"/>
  <text x="470" y="282" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="13" font-weight="700" letter-spacing=".8">$safeCategory</text>
  <text x="470" y="300" fill="#f5f7ff" font-family="Inter, Arial, sans-serif" font-size="11" font-weight="500">KOVER VIDEO</text>
</svg>
SVG;
    }

    private function snapshotArchive(VideoArchive $archive): array
    {
        return [
            'title' => $archive->title,
            'description' => $archive->description,
            'category' => $archive->category,
            'issue' => $archive->issue,
            'age_rating' => $archive->age_rating,
            'status' => $archive->status,
            'air_date' => $archive->air_date?->format('Y-m-d'),
            'air_time' => $archive->air_time ? substr((string) $archive->air_time, 0, 5) : null,
            'video_url' => $archive->video_url,
            'source' => $archive->source,
            'external_id' => $archive->external_id,
            'external_thumbnail_url' => $archive->external_thumbnail_url,
            'external_published_at' => $archive->external_published_at?->format('Y-m-d H:i:s'),
            'duration_seconds' => $archive->duration_seconds,
            'file_path' => $archive->file_path,
            'thumbnail_path' => $archive->thumbnail_path,
            'original_name' => $archive->original_name,
            'mime_type' => $archive->mime_type,
            'file_size' => $archive->file_size,
        ];
    }

    private function buildChangeSet(array $before, array $after): array
    {
        $labels = [
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'category' => 'Kategori',
            'issue' => 'Issue',
            'age_rating' => 'Rating usia',
            'status' => 'Status',
            'air_date' => 'Tanggal tayang',
            'air_time' => 'Jam tayang',
            'video_url' => 'Link video',
            'source' => 'Sumber',
            'external_id' => 'ID eksternal',
            'external_thumbnail_url' => 'Thumbnail eksternal',
            'external_published_at' => 'Tanggal publish eksternal',
            'duration_seconds' => 'Durasi',
            'file_path' => 'File video',
            'thumbnail_path' => 'Thumbnail',
            'original_name' => 'Nama file',
            'mime_type' => 'Tipe file',
            'file_size' => 'Ukuran file',
        ];

        return collect($before)
            ->filter(function ($value, string $field) use ($after): bool {
                return $this->normalizeAuditValue($value) !== $this->normalizeAuditValue($after[$field] ?? null);
            })
            ->map(function ($value, string $field) use ($after, $labels): array {
                return [
                    'field' => $field,
                    'label' => $labels[$field] ?? Str::headline($field),
                    'before' => $this->formatAuditValue($field, $value),
                    'after' => $this->formatAuditValue($field, $after[$field] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeAuditValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

    private function formatAuditValue(string $field, mixed $value): string
    {
        return match ($field) {
            'age_rating' => $value ? (VideoArchive::AGE_RATINGS[$value] ?? $value) : 'Kosong',
            'air_date' => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : 'Kosong',
            'air_time' => $value ? substr((string) $value, 0, 5) : 'Kosong',
            'duration_seconds' => $value ? $this->formatDurationSeconds((int) $value) : 'Kosong',
            'file_size' => $value ? $this->formatBytes((int) $value) : 'Kosong',
            default => $value !== null && $value !== '' ? (string) $value : 'Kosong',
        };
    }

    private function formatDurationSeconds(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' menit';
        }

        if ($remaining > 0 || $parts === []) {
            $parts[] = $remaining.' detik';
        }

        return implode(' ', $parts);
    }

    private function formatBytes(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return number_format($bytes, $unit === 'B' ? 0 : 1).' '.$unit;
            }

            $bytes /= 1024;
        }

        return $bytes.' B';
    }

    private function logActivity(VideoArchive $archive, int $userId, string $action, array $meta = []): void
    {
        VideoArchiveActivity::create([
            'video_archive_id' => $archive->exists ? $archive->id : null,
            'user_id' => $userId,
            'action' => $action,
            'title_snapshot' => $archive->title,
            'meta' => $meta,
        ]);
    }

    private function scheduleConflictWarning(array $data, ?VideoArchive $currentArchive = null): ?string
    {
        if (blank($data['air_date'] ?? null) || blank($data['air_time'] ?? null)) {
            return null;
        }

        $conflictCount = VideoArchive::whereDate('air_date', $data['air_date'])
            ->where('air_time', $data['air_time'])
            ->when($currentArchive, fn ($query) => $query->whereKeyNot($currentArchive->id))
            ->count();

        if ($conflictCount < 1) {
            return null;
        }

        return 'Ada '.$conflictCount.' arsip lain dengan jadwal tayang yang sama. Periksa kembali agar tidak bentrok.';
    }

}




