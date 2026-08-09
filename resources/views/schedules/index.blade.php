@extends('layouts.app')
@section('title', 'Jadwal Tayang')
@section('content')
@php
    $statusFilter = $filters['status'] ?? '';
    $categoryFilter = $filters['category'] ?? '';
    $baseMonthQuery = array_filter([
        'status' => $statusFilter,
        'category' => $categoryFilter,
    ]);
    $weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
@endphp

<div class="schedule-page">
    <section class="schedule-hero">
        <div>
            <span class="eyebrow">Broadcast Calendar</span>
            <h1>Jadwal Tayang</h1>
            <p>Pantau agenda tayang ATV berdasarkan tanggal, jam, kategori, dan status.</p>
        </div>
        <nav class="schedule-month-nav" aria-label="Navigasi bulan">
            <a class="schedule-month-arrow" href="{{ route('schedules.index', array_merge($baseMonthQuery, ['month' => $prevMonth])) }}" aria-label="Bulan sebelumnya">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <a class="schedule-month-current" href="{{ route('schedules.index', array_merge($baseMonthQuery, ['month' => now()->format('Y-m')])) }}">
                <strong>{{ $monthLabel }}</strong>
            </a>
            <a class="schedule-month-arrow" href="{{ route('schedules.index', array_merge($baseMonthQuery, ['month' => $nextMonth])) }}" aria-label="Bulan berikutnya">
                <span aria-hidden="true">&#8594;</span>
            </a>
        </nav>
    </section>

    <div class="schedule-metrics">
        <div class="schedule-metric">
            <span>Terjadwal bulan ini</span>
            <strong>{{ $stats['scheduled_this_month'] }}</strong>
            <small>{{ $monthLabel }}</small>
        </div>
        <div class="schedule-metric blue">
            <span>Siap tayang</span>
            <strong>{{ $stats['ready_this_month'] }}</strong>
            <small>Dalam bulan aktif</small>
        </div>
        <div class="schedule-metric green">
            <span>Tayang hari ini</span>
            <strong>{{ $stats['today'] }}</strong>
            <small>{{ now()->format('d M Y') }}</small>
        </div>
        <div class="schedule-metric orange">
            <span>Belum dijadwalkan</span>
            <strong>{{ $stats['unscheduled'] }}</strong>
            <small>Semua arsip</small>
        </div>
    </div>

    <form class="schedule-filter card" method="get" action="{{ route('schedules.index') }}">
        <label>Bulan
            <input type="month" name="month" value="{{ $currentMonth->format('Y-m') }}">
        </label>
        <label>Status
            <select name="status">
                <option value="">Semua status</option>
                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </label>
        <label>Kategori
            <select name="category">
                <option value="">Semua kategori</option>
                @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                    <option value="{{ $category }}" @selected($categoryFilter === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn primary">Terapkan</button>
        <a class="btn reset-filter" href="{{ route('schedules.index') }}">Reset</a>
    </form>

    @if($scheduleConflicts->isNotEmpty())
        <section class="card schedule-conflict-panel">
            <div class="card-head">
                <div>
                    <h2>Peringatan Jadwal Bentrok</h2>
                    <small>Beberapa arsip punya tanggal dan jam tayang yang sama</small>
                </div>
            </div>
            <div class="schedule-conflict-list">
                @foreach($scheduleConflicts as $conflict)
                    <div>
                        <strong>{{ \Illuminate\Support\Carbon::parse($conflict->air_date)->format('d M Y') }}, {{ substr((string) $conflict->air_time, 0, 5) }}</strong>
                        <span>{{ $conflict->total }} arsip terjadwal di waktu ini</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="schedule-layout">
        <section class="schedule-calendar card">
            <div class="schedule-calendar-head">
                <div>
                    <span class="eyebrow">Kalender</span>
                    <h2>{{ $monthLabel }}</h2>
                </div>
                <span>{{ $calendarDays->sum(fn ($day) => $day['items']->count()) }} jadwal tampil</span>
            </div>

            <div class="schedule-weekdays" aria-hidden="true">
                @foreach($weekDays as $dayName)
                    <span>{{ $dayName }}</span>
                @endforeach
            </div>

            <div class="schedule-calendar-grid">
                @foreach($calendarDays as $day)
                    <div class="schedule-day {{ $day['is_current_month'] ? '' : 'muted' }} {{ $day['is_today'] ? 'today' : '' }}">
                        <div class="schedule-day-head">
                            <strong>{{ $day['date']->format('d') }}</strong>
                            @if($day['items']->isNotEmpty())
                                <span>{{ $day['items']->count() }}</span>
                            @endif
                        </div>

                        <div class="schedule-day-list">
                            @foreach($day['items']->take(3) as $archive)
                                <a class="schedule-chip status-{{ str($archive->status)->slug() }}" href="{{ route('archives.show', $archive) }}">
                                    <b>{{ $archive->air_time ? substr((string) $archive->air_time, 0, 5) : '--:--' }}</b>
                                    <span>{{ $archive->title }}</span>
                                </a>
                            @endforeach

                            @if($day['items']->count() > 3)
                                <small>+{{ $day['items']->count() - 3 }} jadwal lain</small>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="schedule-side">
            <section class="card schedule-panel today-panel">
                <div class="card-head">
                    <div>
                        <h2>Tayang Hari Ini</h2>
                        <small>Agenda berdasarkan jam tayang</small>
                    </div>
                </div>
                <div class="schedule-agenda-list">
                    @forelse($todayArchives as $archive)
                        <a class="schedule-agenda-item" href="{{ route('archives.show', $archive) }}">
                            <time>{{ $archive->air_time ? substr((string) $archive->air_time, 0, 5) : '--:--' }}</time>
                            <div>
                                <strong>{{ $archive->title }}</strong>
                                <small>{{ $archive->category }} - {{ $archive->status }}</small>
                            </div>
                        </a>
                    @empty
                        <p class="empty">Tidak ada jadwal tayang hari ini.</p>
                    @endforelse
                </div>
            </section>

            <section class="card schedule-panel">
                <div class="card-head">
                    <div>
                        <h2>Jadwal Terdekat</h2>
                        <small>Antrian tayang berikutnya</small>
                    </div>
                </div>
                <div class="schedule-upcoming">
                    @forelse($upcomingArchives as $archive)
                        <a class="schedule-upcoming-item" href="{{ route('archives.show', $archive) }}">
                            <div class="schedule-date-badge">
                                <strong>{{ $archive->air_date->format('d') }}</strong>
                                <span>{{ $archive->air_date->format('M') }}</span>
                            </div>
                            <div>
                                <strong>{{ $archive->title }}</strong>
                                <small>{{ $archive->formatted_air_schedule }} - {{ $archive->category }}</small>
                            </div>
                            <span class="badge status-{{ str($archive->status)->slug() }}">{{ $archive->status }}</span>
                        </a>
                    @empty
                        <p class="empty">Belum ada jadwal terdekat.</p>
                    @endforelse
                </div>
            </section>

            <section class="card schedule-panel">
                <div class="card-head">
                    <div>
                        <h2>Belum Dijadwalkan</h2>
                        <small>Lengkapi rencana tayang arsip</small>
                    </div>
                </div>
                <div class="schedule-unscheduled">
                    @forelse($unscheduledArchives as $archive)
                        <a href="{{ route('archives.edit', $archive) }}">
                            <span class="unscheduled-marker" aria-hidden="true"></span>
                            <div>
                                <strong>{{ $archive->title }}</strong>
                                <small>{{ $archive->category }}</small>
                            </div>
                            <span class="badge status-{{ str($archive->status)->slug() }}">{{ $archive->status }}</span>
                            <span class="unscheduled-action">Atur</span>
                        </a>
                    @empty
                        <p class="empty">Semua arsip sudah punya jadwal.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
