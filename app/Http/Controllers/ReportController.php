<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use App\Support\SimplePdfExporter;
use App\Support\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'defaultTemplate' => $this->defaultTemplate(),
        ]);
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'template_text' => ['nullable', 'string', 'max:20000'],
            'template_file' => ['nullable', 'file', 'max:10240'],
            'format' => ['required', Rule::in(['xlsx', 'pdf'])],
            'category' => ['nullable', Rule::in(VideoArchive::CATEGORIES)],
            'issue' => ['nullable', Rule::in(VideoArchive::ISSUES)],
            'status' => ['nullable', Rule::in(VideoArchive::STATUSES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $archives = $this->query($request)->get();
        $summary = $this->summary($archives);
        $content = $this->renderTemplate($request, $data, $summary, $archives);
        $rows = $this->reportRows($content, $summary, $archives);
        $filename = (string) str($data['title'])->slug().'-'.now()->format('Ymd_His').'.'.$data['format'];

        if ($data['format'] === 'pdf') {
            return response(SimplePdfExporter::make($data['title'], ['Bagian', 'Isi'], $rows), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response(SimpleXlsxExporter::make($data['title'], ['Bagian', 'Isi'], $rows), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function query(Request $request)
    {
        return VideoArchive::with('user')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->when($request->filled('issue'), fn ($query) => $query->where('issue', $request->issue))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('air_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('air_date', '<=', $request->date_to))
            ->latest();
    }

    private function summary($archives): array
    {
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
        ];
    }

    private function renderTemplate(Request $request, array $data, array $summary, $archives): string
    {
        $template = $this->templateContent($request, $data);
        $replacements = [
            '{{tanggal}}' => now()->format('d M Y H:i'),
            '{{periode}}' => $this->periodLabel($request),
            '{{daftar_video}}' => $archives->take(20)->map(fn (VideoArchive $archive, int $index) => ($index + 1).'. '.$archive->title.' - '.$archive->category.' - '.$archive->status)->implode("\n"),
        ];

        foreach ($summary as $key => $value) {
            $replacements['{{'.$key.'}}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function templateContent(Request $request, array $data): string
    {
        if ($request->hasFile('template_file')) {
            $content = @file_get_contents($request->file('template_file')->getRealPath()) ?: '';
            $content = preg_replace('/\x00+/', '', $content) ?: '';
            $content = trim($content);

            if ($content !== '') {
                return $content;
            }
        }

        if (! empty($data['template_text'])) {
            return $data['template_text'];
        }

        return $this->defaultTemplate();
    }

    private function reportRows(string $content, array $summary, $archives): array
    {
        $rows = collect(preg_split('/\r\n|\r|\n/', $content) ?: [])
            ->filter(fn ($line) => trim($line) !== '')
            ->map(fn ($line) => ['Template', trim($line)])
            ->values();

        foreach ($summary as $label => $value) {
            $rows->push(['Ringkasan', str($label)->replace('_', ' ')->title().': '.$value]);
        }

        foreach ($archives as $archive) {
            $rows->push([
                'Video',
                $archive->title.' | '.$archive->category.' | '.$archive->issue.' | '.$archive->status.' | '.($archive->air_date?->format('Y-m-d') ?? '-'),
            ]);
        }

        return $rows->all();
    }

    private function periodLabel(Request $request): string
    {
        $from = $request->filled('date_from') ? $request->date_from : 'awal';
        $to = $request->filled('date_to') ? $request->date_to : 'hari ini';

        return $from.' sampai '.$to;
    }

    private function defaultTemplate(): string
    {
        return "LAPORAN ARSIP VIDEO ATV\nTanggal export: {{tanggal}}\nPeriode: {{periode}}\n\nTotal arsip: {{total_arsip}}\nNews: {{total_news}}\nIklan Layanan Masyarakat: {{total_iklan_layanan_masyarakat}}\nProgram: {{total_program}}\nSiap tayang: {{total_siap_tayang}}\nSudah tayang: {{total_sudah_tayang}}\n\nDaftar video:\n{{daftar_video}}";
    }
}
