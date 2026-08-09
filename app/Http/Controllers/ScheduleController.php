<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(VideoArchive::STATUSES)],
            'category' => ['nullable', Rule::in(VideoArchive::CATEGORIES)],
        ]);

        $currentMonth = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $calendarStart = $currentMonth->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $scheduledQuery = VideoArchive::with('user')
            ->whereNotNull('air_date')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category));

        $scheduledArchives = (clone $scheduledQuery)
            ->whereBetween('air_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->orderBy('air_date')
            ->orderBy('air_time')
            ->orderBy('title')
            ->get();

        $archivesByDate = $scheduledArchives->groupBy(fn (VideoArchive $archive) => $archive->air_date->format('Y-m-d'));
        $calendarDays = collect(CarbonPeriod::create($calendarStart, $calendarEnd))
            ->map(fn (Carbon $date) => [
                'date' => $date->copy(),
                'items' => $archivesByDate->get($date->format('Y-m-d'), collect()),
                'is_current_month' => $date->isSameMonth($currentMonth),
                'is_today' => $date->isToday(),
            ]);

        $monthRange = [
            $currentMonth->copy()->startOfMonth()->toDateString(),
            $currentMonth->copy()->endOfMonth()->toDateString(),
        ];

        $todayArchives = (clone $scheduledQuery)
            ->whereDate('air_date', now()->toDateString())
            ->orderBy('air_time')
            ->orderBy('title')
            ->get();

        $upcomingArchives = (clone $scheduledQuery)
            ->whereDate('air_date', '>=', now()->toDateString())
            ->orderBy('air_date')
            ->orderBy('air_time')
            ->orderBy('title')
            ->get();

        $unscheduledArchives = VideoArchive::with('user')
            ->whereNull('air_date')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest()
            ->get();

        $scheduleConflicts = (clone $scheduledQuery)
            ->selectRaw('air_date, air_time, COUNT(*) as total')
            ->whereNotNull('air_time')
            ->whereBetween('air_date', $monthRange)
            ->groupBy('air_date', 'air_time')
            ->having('total', '>', 1)
            ->orderBy('air_date')
            ->orderBy('air_time')
            ->get();

        $stats = [
            'scheduled_this_month' => (clone $scheduledQuery)->whereBetween('air_date', $monthRange)->count(),
            'ready_this_month' => (clone $scheduledQuery)->where('status', 'Siap Tayang')->whereBetween('air_date', $monthRange)->count(),
            'today' => $todayArchives->count(),
            'unscheduled' => VideoArchive::whereNull('air_date')->count(),
        ];

        return view('schedules.index', [
            'calendarDays' => $calendarDays,
            'currentMonth' => $currentMonth,
            'monthLabel' => $this->monthLabel($currentMonth),
            'prevMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
            'filters' => $filters,
            'stats' => $stats,
            'todayArchives' => $todayArchives,
            'upcomingArchives' => $upcomingArchives,
            'unscheduledArchives' => $unscheduledArchives,
            'scheduleConflicts' => $scheduleConflicts,
        ]);
    }

    private function monthLabel(Carbon $date): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
