<?php

namespace App\Http\Controllers;

use App\Models\VideoArchiveActivity;
use App\Models\VideoArchive;
use App\Services\CategoryDetector;
use App\Support\SimplePdfExporter;
use App\Support\SimpleXlsxExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function index(Request $request)
    {
        $archives = $this->archiveQuery($request)->paginate(10)->withQueryString();

        return view('archives.index', compact('archives'));
    }

    public function create() { return view('archives.create'); }

    public function store(Request $request)
    {
        $data = $this->validated($request, true, true);
        $files = $request->file('video');
        $createdCount = 0;

        DB::transaction(function () use ($request, $data, $files, &$createdCount): void {
            foreach ($files as $index => $file) {
                $archiveData = $data;
                $archiveData['user_id'] = $request->user()->id;
                $archiveData['title'] = $this->titleForFile($data['title'], $file->getClientOriginalName(), count($files) > 1, $index + 1);
                $archiveData['file_path'] = $file->store('videos', 'public');
                $archiveData['thumbnail_path'] = $this->storeThumbnail($archiveData['title'], $archiveData['category'], $archiveData['issue']);
                $archiveData['original_name'] = $file->getClientOriginalName();
                $archiveData['mime_type'] = $file->getMimeType();
                $archiveData['file_size'] = $file->getSize();
                unset($archiveData['video']);

                $archive = VideoArchive::create($archiveData);
                $this->logActivity($archive, $request->user()->id, 'created', [
                    'original_name' => $file->getClientOriginalName(),
                    'bulk' => count($files) > 1,
                ]);
                $createdCount++;
            }
        });

        return redirect()->route('archives.index')->with('success', $createdCount > 1 ? $createdCount.' video berhasil ditambahkan ke arsip.' : 'Video berhasil ditambahkan ke arsip.');
    }

    public function show(VideoArchive $archive) { return view('archives.show', compact('archive')); }
    public function edit(VideoArchive $archive) { return view('archives.edit', compact('archive')); }

    public function update(Request $request, VideoArchive $archive)
    {
        $data = $this->validated($request, false, false);
        $before = $archive->only(['title', 'category', 'issue', 'status', 'air_date', 'video_url']);
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
        unset($data['video']);
        $archive->update($data);
        $this->logActivity($archive, $request->user()->id, 'updated', [
            'before' => $before,
            'after' => $archive->only(['title', 'category', 'issue', 'status', 'air_date', 'video_url']),
        ]);
        return redirect()->route('archives.show', $archive)->with('success', 'Data arsip berhasil diperbarui.');
    }

    public function destroy(VideoArchive $archive)
    {
        Storage::disk('public')->delete($archive->file_path);
        Storage::disk('public')->delete($archive->thumbnail_path);
        $this->logActivity($archive, auth()->id(), 'deleted', [
            'title' => $archive->title,
            'category' => $archive->category,
            'issue' => $archive->issue,
        ]);
        $archive->delete();
        return redirect()->route('archives.index')->with('success', 'Arsip berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $format = $request->query('format', 'xlsx');
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);

        $filename = 'arsip-video-'.now()->format('Ymd_His').'.'.$format;
        $columns = ['Judul', 'Kategori', 'Issue', 'Status', 'Rencana Tayang', 'Link Video', 'Pengunggah', 'Nama File', 'Ukuran', 'Dibuat'];
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
            $archive->status,
            $archive->air_date?->format('Y-m-d'),
            $archive->video_url,
            $archive->user?->name,
            $archive->original_name,
            $archive->formatted_size,
            $archive->created_at?->format('Y-m-d H:i:s'),
        ])->all();
    }

    public function download(VideoArchive $archive)
    {
        abort_unless(Storage::disk('public')->exists($archive->file_path), 404);
        return Storage::disk('public')->download($archive->file_path, $archive->original_name);
    }

    public function preview(VideoArchive $archive)
    {
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
            'status' => ['required', Rule::in(VideoArchive::STATUSES)],
            'air_date' => ['nullable', 'date'],
            'video_url' => ['nullable', 'url', 'max:2048'],
        ];

        if ($bulk) {
            $rules['video'] = ['required', 'array', 'min:1'];
            $rules['video.*'] = ['file', 'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm', 'max:512000'];
        } else {
            $rules['video'] = [$required ? 'required' : 'nullable', 'file', 'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm', 'max:512000'];
        }

        return $request->validate($rules, [
            'video.max' => 'Ukuran video maksimal 500 MB.',
            'video.mimetypes' => 'File harus berupa video MP4, MPEG, MOV, AVI, atau WebM.',
            'video.*.max' => 'Ukuran video maksimal 500 MB.',
            'video.*.mimetypes' => 'Setiap file harus berupa video MP4, MPEG, MOV, AVI, atau WebM.',
        ]);
    }

    private function archiveQuery(Request $request)
    {
        return VideoArchive::with('user')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', '%'.$request->search.'%')
                ->orWhere('description', 'like', '%'.$request->search.'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('issue'), fn ($q) => $q->where('issue', $request->issue))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('air_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('air_date', '<=', $request->date_to))
            ->when($request->filled('sort'), function ($q) use ($request): void {
                if ($request->sort === 'oldest') {
                    $q->orderBy('created_at');

                    return;
                }

                if ($request->sort === 'title') {
                    $q->orderBy('title');

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
}




