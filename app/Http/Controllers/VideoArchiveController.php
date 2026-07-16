<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use App\Services\CategoryDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VideoArchiveController extends Controller
{
    public function detectCategory(Request $request, CategoryDetector $detector)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        return response()->json($detector->detect($data['title'], $data['description'] ?? null));
    }

    public function index(Request $request)
    {
        $archives = VideoArchive::with('user')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', '%'.$request->search.'%')
                ->orWhere('description', 'like', '%'.$request->search.'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('air_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('air_date', '<=', $request->date_to))
            ->latest()->paginate(10)->withQueryString();

        return view('archives.index', compact('archives'));
    }

    public function create() { return view('archives.create'); }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $file = $request->file('video');
        $data += [
            'user_id' => $request->user()->id,
            'file_path' => $file->store('videos', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
        unset($data['video']);
        VideoArchive::create($data);
        return redirect()->route('archives.index')->with('success', 'Video berhasil ditambahkan ke arsip.');
    }

    public function show(VideoArchive $archive) { return view('archives.show', compact('archive')); }
    public function edit(VideoArchive $archive) { return view('archives.edit', compact('archive')); }

    public function update(Request $request, VideoArchive $archive)
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('video')) {
            Storage::disk('public')->delete($archive->file_path);
            $file = $request->file('video');
            $data += ['file_path' => $file->store('videos', 'public'), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize()];
        }
        unset($data['video']);
        $archive->update($data);
        return redirect()->route('archives.show', $archive)->with('success', 'Data arsip berhasil diperbarui.');
    }

    public function destroy(VideoArchive $archive)
    {
        Storage::disk('public')->delete($archive->file_path);
        $archive->delete();
        return redirect()->route('archives.index')->with('success', 'Arsip berhasil dihapus.');
    }

    public function download(VideoArchive $archive)
    {
        abort_unless(Storage::disk('public')->exists($archive->file_path), 404);
        return Storage::disk('public')->download($archive->file_path, $archive->original_name);
    }

    private function validated(Request $request, bool $required): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(VideoArchive::CATEGORIES)],
            'status' => ['required', Rule::in(VideoArchive::STATUSES)],
            'air_date' => ['nullable', 'date'],
            'video' => [$required ? 'required' : 'nullable', 'file', 'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm', 'max:512000'],
        ], ['video.max' => 'Ukuran video maksimal 500 MB.', 'video.mimetypes' => 'File harus berupa video MP4, MPEG, MOV, AVI, atau WebM.']);
    }
}
