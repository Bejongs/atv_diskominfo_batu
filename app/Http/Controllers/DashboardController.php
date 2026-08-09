<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use App\Models\VideoArchiveActivity;
use App\Models\VideoArchive;
use App\Services\VideoArchiveStatusSyncer;

class DashboardController extends Controller
{
    public function __invoke(VideoArchiveStatusSyncer $syncer)
    {
        $syncer->syncDueToAired();

        $trendStartDate = now()->subDays(6)->startOfDay();
        $trendEndDate = now()->endOfDay();
        $dailyUploads = VideoArchive::whereBetween('created_at', [$trendStartDate, $trendEndDate])
            ->get(['created_at'])
            ->groupBy(fn (VideoArchive $archive) => $archive->created_at->format('Y-m-d'))
            ->map->count();
        $trendMax = max($dailyUploads->max() ?: 0, 1);
        $uploadTrend = collect(CarbonPeriod::create($trendStartDate, $trendEndDate))
            ->map(function ($date) use ($dailyUploads, $trendMax) {
                $total = $dailyUploads[$date->format('Y-m-d')] ?? 0;

                return [
                    'label' => $date->translatedFormat('d M'),
                    'period' => $date->translatedFormat('l, d M Y'),
                    'total' => $total,
                    'percent' => max(6, round(($total / $trendMax) * 100)),
                ];
            });
        $statusByCategory = collect(VideoArchive::CATEGORIES)->mapWithKeys(function ($category) {
            return [$category => collect(VideoArchive::STATUSES)->mapWithKeys(fn ($status) => [$status => 0])->all()];
        })->all();
        foreach (VideoArchive::selectRaw('category, status, count(*) as total')->groupBy('category', 'status')->get() as $row) {
            $statusByCategory[$row->category][$row->status] = (int) $row->total;
        }

        return view('dashboard', [
            'total' => VideoArchive::count(),
            'ready' => VideoArchive::where('status', 'Siap Tayang')->count(),
            'aired' => VideoArchive::where('status', 'Sudah Tayang')->count(),
            'size' => VideoArchive::sum('file_size'),
            'categories' => VideoArchive::selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
            'issues' => VideoArchive::selectRaw('issue, count(*) as total')->whereNotNull('issue')->groupBy('issue')->pluck('total', 'issue'),
            'workflowCounts' => VideoArchive::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'uploadTrend' => $uploadTrend,
            'statusByCategory' => $statusByCategory,
            'latest' => VideoArchive::with('user')->latest()->limit(5)->get(),
            'activities' => VideoArchiveActivity::with(['user', 'archive'])->latest()->limit(6)->get(),
        ]);
    }
}
