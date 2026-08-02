<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use App\Support\SimplePdfExporter;
use App\Support\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->baseQuery($request);
        $stats = $this->statsFromQuery($query);

        return view('reports.index', [
            'filters' => $this->filters($request),
            'stats' => $stats,
            'summaryCards' => $this->summaryCardsFromStats($stats),
            'categoryBreakdown' => $this->categoryBreakdownFromQuery($query),
            'statusBreakdown' => $this->statusBreakdownFromQuery($query),
            'ageRatingBreakdown' => $this->ageRatingBreakdownFromQuery($query),
            'recentArchives' => (clone $query)->with('user')->latest()->limit(20)->get(),
        ]);
    }

    public function export(Request $request)
    {
        $this->normalizeArrayFilters($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'format' => ['required', Rule::in(['xlsx', 'pdf'])],
            'category' => ['nullable', 'array'],
            'category.*' => [Rule::in(VideoArchive::CATEGORIES)],
            'issue' => ['nullable', 'array'],
            'issue.*' => [Rule::in(VideoArchive::ISSUES)],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(VideoArchive::STATUSES)],
            'age_rating' => ['nullable', 'array'],
            'age_rating.*' => [Rule::in(array_keys(VideoArchive::AGE_RATINGS))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $query = $this->baseQuery($request);
        $stats = $this->statsFromQuery($query);
        $report = $this->reportPayload(
            $request,
            $stats,
            $data['title'],
            $this->categoryBreakdownFromQuery($query),
            $this->statusBreakdownFromQuery($query),
            $this->ageRatingBreakdownFromQuery($query),
        );
        $filename = (string) str($data['title'])->slug().'-'.now()->format('Ymd_His').'.'.$data['format'];

        if ($data['format'] === 'pdf') {
            return response(SimplePdfExporter::makeReport($report), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $archives = $this->query($request)->get();

        return response(SimpleXlsxExporter::make($data['title'], $this->reportArchiveColumns(), $this->reportArchiveRows($archives)), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function baseQuery(Request $request)
    {
        $categories = $this->filterValues($request, 'category');
        $issues = $this->filterValues($request, 'issue');
        $statuses = $this->filterValues($request, 'status');
        $ageRatings = $this->filterValues($request, 'age_rating');

        return VideoArchive::query()
            ->when($categories->isNotEmpty(), fn ($query) => $query->whereIn('category', $categories))
            ->when($issues->isNotEmpty(), fn ($query) => $query->whereIn('issue', $issues))
            ->when($statuses->isNotEmpty(), fn ($query) => $query->whereIn('status', $statuses))
            ->when($ageRatings->isNotEmpty(), fn ($query) => $query->whereIn('age_rating', $ageRatings))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('air_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('air_date', '<=', $request->date_to));
    }

    private function query(Request $request)
    {
        return $this->baseQuery($request)
            ->with('user')
            ->latest();
    }

    private function filters(Request $request): array
    {
        $categories = $this->filterValues($request, 'category');
        $issues = $this->filterValues($request, 'issue');
        $statuses = $this->filterValues($request, 'status');
        $ageRatings = $this->filterValues($request, 'age_rating');

        return [
            'period_label' => $this->periodLabel($request),
            'category' => $categories->isNotEmpty() ? $categories->all() : null,
            'issue' => $issues->isNotEmpty() ? $issues->all() : null,
            'status' => $statuses->isNotEmpty() ? $statuses->all() : null,
            'age_rating' => $ageRatings->isNotEmpty() ? $ageRatings->all() : null,
            'category_value' => $categories->all(),
            'issue_value' => $issues->all(),
            'status_value' => $statuses->all(),
            'age_rating_value' => $ageRatings->all(),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];
    }

    private function filterValues(Request $request, string $key)
    {
        return collect((array) $request->input($key))->filter()->values();
    }

    private function normalizeArrayFilters(Request $request): void
    {
        foreach (['category', 'issue', 'status', 'age_rating'] as $key) {
            if ($request->filled($key) && ! is_array($request->input($key))) {
                $request->merge([$key => [$request->input($key)]]);
            }
        }
    }

    private function stats($archives): array
    {
        $totalDurationSeconds = $archives->sum('duration_seconds');
        $archivesWithFiles = $archives->filter(fn (VideoArchive $archive) => filled($archive->file_path));
        $archivesWithDuration = $archives->filter(fn (VideoArchive $archive) => filled($archive->duration_seconds) || filled($archive->duration_minutes));

        return [
            'total_arsip' => $archives->count(),
            'total_news' => $archives->where('category', 'News')->count(),
            'total_iklan_layanan_masyarakat' => $archives->where('category', 'Iklan Layanan Masyarakat')->count(),
            'total_program' => $archives->where('category', 'Program')->count(),
            'total_siap_tayang' => $archives->where('status', 'Siap Tayang')->count(),
            'total_sudah_tayang' => $archives->where('status', 'Sudah Tayang')->count(),
            'total_draft' => $archives->where('status', 'Draft')->count(),
            'total_review' => $archives->where('status', 'Review')->count(),
            'total_diarsipkan' => $archives->where('status', 'Diarsipkan')->count(),
            'total_without_file' => $archives->filter(fn (VideoArchive $archive) => blank($archive->file_path))->count(),
            'file_ready' => $archivesWithFiles->count(),
            'total_duration' => $this->formatSeconds($totalDurationSeconds),
            'average_duration' => $this->formatSeconds((int) round($archivesWithDuration->avg('duration_seconds') ?: 0)),
            'average_size' => $archivesWithFiles->count() ? $this->formatBytes((int) round($archivesWithFiles->avg('file_size') ?: 0)) : '0 B',
        ];
    }

    private function statsFromQuery($query): array
    {
        $row = (clone $query)
            ->selectRaw('COUNT(*) as total_arsip')
            ->selectRaw("SUM(CASE WHEN category = 'News' THEN 1 ELSE 0 END) as total_news")
            ->selectRaw("SUM(CASE WHEN category = 'Iklan Layanan Masyarakat' THEN 1 ELSE 0 END) as total_iklan_layanan_masyarakat")
            ->selectRaw("SUM(CASE WHEN category = 'Program' THEN 1 ELSE 0 END) as total_program")
            ->selectRaw("SUM(CASE WHEN status = 'Siap Tayang' THEN 1 ELSE 0 END) as total_siap_tayang")
            ->selectRaw("SUM(CASE WHEN status = 'Sudah Tayang' THEN 1 ELSE 0 END) as total_sudah_tayang")
            ->selectRaw("SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as total_draft")
            ->selectRaw("SUM(CASE WHEN status = 'Review' THEN 1 ELSE 0 END) as total_review")
            ->selectRaw("SUM(CASE WHEN status = 'Diarsipkan' THEN 1 ELSE 0 END) as total_diarsipkan")
            ->selectRaw("SUM(CASE WHEN file_path IS NULL OR file_path = '' THEN 1 ELSE 0 END) as total_without_file")
            ->selectRaw("SUM(CASE WHEN file_path IS NOT NULL AND file_path != '' THEN 1 ELSE 0 END) as file_ready")
            ->selectRaw('SUM(COALESCE(duration_seconds, duration_minutes * 60, 0)) as total_duration_seconds')
            ->selectRaw('AVG(CASE WHEN duration_seconds IS NOT NULL OR duration_minutes IS NOT NULL THEN COALESCE(duration_seconds, duration_minutes * 60, 0) END) as average_duration_seconds')
            ->selectRaw("AVG(CASE WHEN file_path IS NOT NULL AND file_path != '' THEN file_size END) as average_file_size")
            ->first();

        return [
            'total_arsip' => (int) ($row->total_arsip ?? 0),
            'total_news' => (int) ($row->total_news ?? 0),
            'total_iklan_layanan_masyarakat' => (int) ($row->total_iklan_layanan_masyarakat ?? 0),
            'total_program' => (int) ($row->total_program ?? 0),
            'total_siap_tayang' => (int) ($row->total_siap_tayang ?? 0),
            'total_sudah_tayang' => (int) ($row->total_sudah_tayang ?? 0),
            'total_draft' => (int) ($row->total_draft ?? 0),
            'total_review' => (int) ($row->total_review ?? 0),
            'total_diarsipkan' => (int) ($row->total_diarsipkan ?? 0),
            'total_without_file' => (int) ($row->total_without_file ?? 0),
            'file_ready' => (int) ($row->file_ready ?? 0),
            'total_duration' => $this->formatSeconds((int) ($row->total_duration_seconds ?? 0)),
            'average_duration' => $this->formatSeconds((int) round($row->average_duration_seconds ?? 0)),
            'average_size' => $this->formatBytes((int) round($row->average_file_size ?? 0)),
        ];
    }

    private function summaryCardsFromStats(array $stats): array
    {
        return [
            ['label' => 'Total arsip', 'value' => $stats['total_arsip'], 'hint' => 'Semua data yang cocok filter'],
            ['label' => 'Sudah tayang', 'value' => $stats['total_sudah_tayang'], 'hint' => 'Konten yang sudah aktif'],
            ['label' => 'Siap tayang', 'value' => $stats['total_siap_tayang'], 'hint' => 'Menunggu jadwal'],
            ['label' => 'Belum ada file', 'value' => $stats['total_without_file'], 'hint' => 'Hanya link atau metadata'],
        ];
    }

    private function summaryCards($archives): array
    {
        return [
            ['label' => 'Total arsip', 'value' => $archives->count(), 'hint' => 'Semua data yang cocok filter'],
            ['label' => 'Sudah tayang', 'value' => $archives->where('status', 'Sudah Tayang')->count(), 'hint' => 'Konten yang sudah aktif'],
            ['label' => 'Siap tayang', 'value' => $archives->where('status', 'Siap Tayang')->count(), 'hint' => 'Menunggu jadwal'],
            ['label' => 'Belum ada file', 'value' => $archives->filter(fn (VideoArchive $archive) => blank($archive->file_path))->count(), 'hint' => 'Hanya link atau metadata'],
        ];
    }

    private function categoryBreakdown($archives): array
    {
        $total = max($archives->count(), 1);

        return collect(VideoArchive::CATEGORIES)->map(fn ($category) => [
            'label' => $category,
            'count' => $archives->where('category', $category)->count(),
            'percent' => (int) round(($archives->where('category', $category)->count() / $total) * 100),
        ])->all();
    }

    private function statusBreakdown($archives): array
    {
        $total = max($archives->count(), 1);

        return collect(VideoArchive::STATUSES)->map(fn ($status) => [
            'label' => $status,
            'count' => $archives->where('status', $status)->count(),
            'percent' => (int) round(($archives->where('status', $status)->count() / $total) * 100),
        ])->all();
    }

    private function ageRatingBreakdown($archives): array
    {
        $total = max($archives->count(), 1);

        return collect(VideoArchive::AGE_RATINGS)->map(function ($label, $code) use ($archives, $total) {
            $count = $archives->where('age_rating', $code)->count();

            return [
                'code' => $code,
                'label' => $label,
                'count' => $count,
                'percent' => (int) round(($count / $total) * 100),
            ];
        })->push([
            'code' => '',
            'label' => 'Belum dipilih',
            'count' => $archives->whereNull('age_rating')->count(),
            'percent' => (int) round(($archives->whereNull('age_rating')->count() / $total) * 100),
        ])->values()->all();
    }

    private function categoryBreakdownFromQuery($query): array
    {
        $counts = (clone $query)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');
        $total = max((int) $counts->sum(), 1);

        return collect(VideoArchive::CATEGORIES)->map(fn ($category) => [
            'label' => $category,
            'count' => (int) ($counts[$category] ?? 0),
            'percent' => (int) round(((int) ($counts[$category] ?? 0) / $total) * 100),
        ])->all();
    }

    private function statusBreakdownFromQuery($query): array
    {
        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $total = max((int) $counts->sum(), 1);

        return collect(VideoArchive::STATUSES)->map(fn ($status) => [
            'label' => $status,
            'count' => (int) ($counts[$status] ?? 0),
            'percent' => (int) round(((int) ($counts[$status] ?? 0) / $total) * 100),
        ])->all();
    }

    private function ageRatingBreakdownFromQuery($query): array
    {
        $counts = (clone $query)
            ->selectRaw('age_rating, COUNT(*) as total')
            ->groupBy('age_rating')
            ->pluck('total', 'age_rating');
        $total = max((int) $counts->sum(), 1);

        return collect(VideoArchive::AGE_RATINGS)->map(function ($label, $code) use ($counts, $total) {
            $count = (int) ($counts[$code] ?? 0);

            return [
                'code' => $code,
                'label' => $label,
                'count' => $count,
                'percent' => (int) round(($count / $total) * 100),
            ];
        })->push([
            'code' => '',
            'label' => 'Belum dipilih',
            'count' => (int) ($counts[''] ?? 0),
            'percent' => (int) round(((int) ($counts[''] ?? 0) / $total) * 100),
        ])->values()->all();
    }

    private function reportPayload(Request $request, array $stats, string $title, array $categoryBreakdown, array $statusBreakdown, array $ageRatingBreakdown): array
    {
        return [
            'title' => $title,
            'generated_at' => now()->format('d M Y H:i'),
            'period' => $this->periodLabel($request),
            'summary_cards' => [
                [
                    'label' => 'Total arsip',
                    'value' => $stats['total_arsip'],
                    'hint' => 'Semua data terfilter',
                    'color' => '6D28D9',
                ],
                [
                    'label' => 'Sudah tayang',
                    'value' => $stats['total_sudah_tayang'],
                    'hint' => 'Konten yang sudah live',
                    'color' => '2563EB',
                ],
                [
                    'label' => 'Tanpa file',
                    'value' => $stats['total_without_file'],
                    'hint' => 'Hanya link atau metadata',
                    'color' => '16A34A',
                ],
                [
                    'label' => 'Durasi rata-rata',
                    'value' => $stats['average_duration'],
                    'hint' => 'Berdasarkan file yang ada',
                    'color' => 'F97316',
                ],
                [
                    'label' => 'Ukuran rata-rata',
                    'value' => $stats['average_size'],
                    'hint' => 'Rata-rata file arsip',
                    'color' => '0F766E',
                ],
            ],
            'charts' => [
                [
                    'title' => 'Distribusi Kategori',
                    'subtitle' => 'Komposisi kategori arsip aktif',
                    'color' => '2563EB',
                    'items' => array_map(function (array $item) {
                        $item['color'] = $this->categoryColor($item['label']);
                        return $item;
                    }, $categoryBreakdown),
                ],
                [
                    'title' => 'Distribusi Status',
                    'subtitle' => 'Workflow arsip video',
                    'color' => '16A34A',
                    'items' => array_map(function (array $item) {
                        $item['color'] = $this->statusColor($item['label']);
                        return $item;
                    }, $statusBreakdown),
                ],
                [
                    'title' => 'Rating Usia',
                    'subtitle' => 'Penyesuaian penonton video',
                    'color' => '7C3AED',
                    'items' => array_map(function (array $item) {
                        $item['color'] = $this->ageRatingColor($item['code']);
                        return $item;
                    }, $ageRatingBreakdown),
                ],
            ],
            'technical' => [
                ['label' => 'Total durasi', 'value' => $stats['total_duration']],
                ['label' => 'Rata-rata ukuran file', 'value' => $stats['average_size']],
                ['label' => 'File tersedia', 'value' => $stats['file_ready']],
                ['label' => 'Catatan', 'value' => $stats['total_without_file'] > 0 ? 'Lengkapi file arsip yang masih kosong.' : 'Data sudah rapi dan konsisten.'],
            ],
        ];
    }

    private function reportArchiveColumns(): array
    {
        return ['Judul', 'Kategori', 'Issue', 'Rating Usia', 'Status', 'Durasi', 'Rencana Tayang', 'Link Video', 'Pengunggah', 'Nama File', 'Ukuran', 'Dibuat'];
    }

    private function reportArchiveRows($archives): array
    {
        return $archives->map(fn (VideoArchive $archive) => [
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

    private function categoryColor(string $label): string
    {
        return match ($label) {
            'News' => '2563EB',
            'Iklan Layanan Masyarakat' => '16A34A',
            'Program' => 'F97316',
            default => '64748B',
        };
    }

    private function statusColor(string $label): string
    {
        return match ($label) {
            'Draft' => '8B5CF6',
            'Review' => 'F59E0B',
            'Siap Tayang' => '2563EB',
            'Sudah Tayang' => '16A34A',
            'Diarsipkan' => '64748B',
            default => '94A3B8',
        };
    }

    private function ageRatingColor(?string $code): string
    {
        return match ($code) {
            'SU' => '2563EB',
            'A' => '16A34A',
            'R' => 'F97316',
            'D' => 'DC2626',
            default => '8B5CF6',
        };
    }

    private function reportRows(Request $request, $archives, array $stats): array
    {
        $rows = [
            ['Periode', $this->periodLabel($request), 'Filter tanggal tayang yang digunakan'],
            ['Total arsip', $stats['total_arsip'], 'Jumlah seluruh arsip pada filter aktif'],
            ['Sudah tayang', $stats['total_sudah_tayang'].' ('.$this->percent($stats['total_sudah_tayang'], $stats['total_arsip']).'%)', 'Konten yang sudah masuk status tayang'],
            ['Siap tayang', $stats['total_siap_tayang'].' ('.$this->percent($stats['total_siap_tayang'], $stats['total_arsip']).'%)', 'Konten yang siap masuk jadwal tayang'],
            ['Tanpa file', $stats['total_without_file'].' ('.$this->percent($stats['total_without_file'], $stats['total_arsip']).'%)', 'Arsip hanya memiliki link atau metadata'],
            ['Total durasi', $stats['total_duration'], 'Akumulasi durasi video yang terdata'],
            ['Durasi rata-rata', $stats['average_duration'], 'Rata-rata dari arsip yang punya durasi'],
            ['Ukuran file rata-rata', $stats['average_size'], 'Rata-rata ukuran file arsip'],
        ];

        foreach ($this->categoryBreakdown($archives) as $item) {
            $rows[] = ['Kategori - '.$item['label'], $item['count'].' arsip ('.$item['percent'].'%)', 'Komposisi kategori terhadap total arsip'];
        }

        foreach ($this->statusBreakdown($archives) as $item) {
            $rows[] = ['Status - '.$item['label'], $item['count'].' video ('.$item['percent'].'%)', 'Komposisi workflow arsip'];
        }

        foreach ($this->ageRatingBreakdown($archives) as $item) {
            $rows[] = ['Rating usia - '.$item['label'], $item['count'].' arsip ('.$item['percent'].'%)', 'Komposisi rating usia penonton'];
        }

        $rows[] = ['Rekomendasi', $this->recommendation($stats), 'Catatan otomatis berdasarkan kondisi data'];

        return $rows;
    }

    private function recommendation(array $stats): string
    {
        if ($stats['total_without_file'] > 0) {
            return 'Lengkapi file video untuk arsip yang masih hanya memiliki link atau metadata agar dokumentasi lebih lengkap.';
        }

        return 'Pertahankan konsistensi pengisian kategori, status, rating usia, durasi, dan jadwal tayang untuk menjaga kualitas laporan.';
    }

    private function percent(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }

    private function periodLabel(Request $request): string
    {
        $from = $request->filled('date_from') ? $request->date_from : 'awal';
        $to = $request->filled('date_to') ? $request->date_to : 'hari ini';

        return $from.' sampai '.$to;
    }

    private function formatSeconds(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 detik';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' menit';
        }

        if ($remain > 0 || $parts === []) {
            $parts[] = $remain.' detik';
        }

        return implode(' ', $parts);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return number_format($bytes, $unit === 'B' ? 0 : 1).' '.$unit;
            }

            $bytes /= 1024;
        }

        return '0 B';
    }
}
