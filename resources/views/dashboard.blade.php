@extends('layouts.app')
@section('title','Dashboard')
@section('content')
@php
    $trendMax = max(collect($uploadTrend)->max('total') ?: 0, 1);
    $trendPlotWidth = 620;
    $trendPlotHeight = 210;
    $trendLeft = 40;
    $trendTop = 22;
    $trendBottom = $trendTop + $trendPlotHeight;
    $trendStep = count($uploadTrend) > 1 ? $trendPlotWidth / (count($uploadTrend) - 1) : 0;
    $trendPoints = collect($uploadTrend)->values()->map(function ($day, $index) use ($trendLeft, $trendTop, $trendPlotHeight, $trendStep, $trendMax) {
        $x = $trendLeft + ($trendStep * $index);
        $y = $trendTop + $trendPlotHeight - (($day['total'] / $trendMax) * $trendPlotHeight);

        return [
            'x' => $x,
            'y' => $y,
            'label' => $day['label'],
            'total' => $day['total'],
            'period' => $day['period'] ?? $day['label'],
        ];
    });
    $trendPath = '';
    $trendPoints->each(function ($point, $index) use ($trendPoints, &$trendPath) {
        if ($index === 0) {
            $trendPath = 'M '.$point['x'].' '.$point['y'];

            return;
        }

        $previous = $trendPoints[$index - 1];
        $controlOffset = ($point['x'] - $previous['x']) / 2;
        $trendPath .= ' C '.($previous['x'] + $controlOffset).' '.$previous['y'].' '.($point['x'] - $controlOffset).' '.$point['y'].' '.$point['x'].' '.$point['y'];
    });
    $trendAreaPath = $trendPoints->isNotEmpty()
        ? $trendPath.' L '.$trendPoints->last()['x'].' '.$trendBottom.' L '.$trendPoints->first()['x'].' '.$trendBottom.' Z'
        : '';
@endphp

<div class="dashboard-page">
    <div class="page-head dashboard-head">
        <div>
            <span class="eyebrow">Overview</span>
            <h1>Dashboard</h1>
            <p>Ringkasan arsip tayangan ATV hari ini.</p>
        </div>
        <div class="dashboard-head-actions">
            @if(auth()->user()?->isSuperAdmin())
                <a class="btn" href="{{ route('backup.data') }}">Backup Data</a>
            @endif
            <a class="btn primary" href="{{ route('archives.upload') }}">&#43; Upload Video</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><span>Total Video</span><strong>{{ $total }}</strong><small>Semua arsip</small></div>
        <div class="stat orange"><span>Siap Tayang</span><strong>{{ $ready }}</strong><small>Menunggu ditayangkan</small></div>
        <div class="stat green"><span>Sudah Tayang</span><strong>{{ $aired }}</strong><small>Video selesai tayang</small></div>
        <div class="stat purple"><span>Penyimpanan</span><strong>{{ number_format($size / 1048576, 1) }} MB</strong><small>Total ukuran file</small></div>
    </div>

    <div class="grid-2 dashboard-top-grid">
        <section class="card dashboard-card">
            <div class="card-head">
                <h2>Arsip terbaru</h2>
                <a href="{{ route('archives.index') }}">Lihat semua</a>
            </div>
            @forelse($latest as $item)
                <a class="recent" href="{{ route('archives.show',$item) }}">
                    <div class="video-icon">&#9654;</div>
                    <div>
                        <strong>{{ $item->title }}</strong>
                        <small>{{ $item->category }} - {{ $item->issue ?? 'Belum ada issue' }} - {{ $item->created_at->diffForHumans() }}</small>
                    </div>
                    <span class="badge status-{{ str($item->status)->slug() }}">{{ $item->status }}</span>
                </a>
            @empty
                <p class="empty">Belum ada video. Mulai dengan mengunggah arsip pertama.</p>
            @endforelse
        </section>

        <section class="card dashboard-card">
            <div class="card-head"><h2>Kategori</h2></div>
            <div class="dashboard-category-visual">
                @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                    @php
                        $categoryTotal = $categories[$category] ?? 0;
                        $categoryPercent = $total ? round(($categoryTotal / $total) * 100) : 0;
                    @endphp
                    <div class="dashboard-category-tile category-{{ str($category)->slug() }}">
                        <div class="dashboard-category-top">
                            <span>{{ $category }}</span>
                            <strong>{{ $categoryTotal }}</strong>
                        </div>
                        <div class="dashboard-category-meter">
                            <i style="width:{{ $categoryPercent }}%"></i>
                        </div>
                        <small>{{ $categoryPercent }}% dari total arsip</small>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="dashboard-visuals">
        <section class="card chart-card">
            <div class="card-head">
                <div>
                    <h2>Grafik Status Workflow</h2>
                    <small>Distribusi proses arsip video</small>
                </div>
            </div>
            <div class="status-chart">
                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                    @php
                        $statusTotal = $workflowCounts[$status] ?? 0;
                        $statusPercent = $total ? round(($statusTotal / $total) * 100) : 0;
                    @endphp
                    <div class="status-chart-row">
                        <div>
                            <strong>{{ $status }}</strong>
                            <span>{{ $statusTotal }} video</span>
                        </div>
                        <div class="chart-track">
                            <i class="status-{{ str($status)->slug() }}" style="width: {{ $statusPercent }}%"></i>
                        </div>
                        <b>{{ $statusPercent }}%</b>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card chart-card">
            <div class="card-head">
                <div>
                    <h2>Grafik Issue</h2>
                    <small>Komposisi ekonomi, lingkungan, dan sosial</small>
                </div>
            </div>
            <div class="issue-chart">
                @foreach(\App\Models\VideoArchive::ISSUES as $issue)
                    @php
                        $issueTotal = $issues[$issue] ?? 0;
                        $issuePercent = $total ? round(($issueTotal / $total) * 100) : 0;
                    @endphp
                    <div class="issue-meter issue-{{ str($issue)->slug() }}">
                        <div class="issue-ring" style="--percent: {{ $issuePercent }};">
                            <span>{{ $issuePercent }}%</span>
                        </div>
                        <strong>{{ $issue }}</strong>
                        <small>{{ $issueTotal }} arsip</small>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="dashboard-visuals dashboard-trend-grid">
        <section class="card chart-card trend-card">
            <div class="card-head">
                <div>
                    <h2>Trend Statistik Upload</h2>
                    <small>Jumlah arsip masuk harian dalam 7 hari terakhir</small>
                </div>
            </div>
            <div class="line-chart-shell interactive-line-chart" data-upload-trend>
                <div class="line-chart-tooltip" data-chart-tooltip hidden>
                    <strong data-chart-tooltip-total>0 upload</strong>
                    <span data-chart-tooltip-label>Jan</span>
                </div>
                <svg class="line-chart-svg" viewBox="0 0 700 300" preserveAspectRatio="none" role="img" aria-label="Trend statistik upload harian tujuh hari terakhir">
                    <defs>
                        <linearGradient id="uploadTrendArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity=".28"/>
                            <stop offset="100%" stop-color="#3b82f6" stop-opacity=".04"/>
                        </linearGradient>
                    </defs>
                    @for($i = 0; $i <= 8; $i++)
                        @php
                            $gridY = $trendTop + ($trendPlotHeight / 8) * $i;
                        @endphp
                        <line x1="40" y1="{{ $gridY }}" x2="660" y2="{{ $gridY }}" class="chart-grid"></line>
                    @endfor

                    @if($trendPoints->count() > 1)
                        <path d="{{ $trendAreaPath }}" class="chart-area"></path>
                        <path d="{{ $trendPath }}" class="chart-line"></path>
                    @endif

                    @foreach($trendPoints as $point)
                        <g class="chart-hit"
                           tabindex="0"
                           data-label="{{ $point['label'] }}: {{ $point['period'] }}"
                           data-total="{{ $point['total'] }}"
                           data-x="{{ $point['x'] }}"
                           data-y="{{ $point['y'] }}">
                            <line x1="{{ $point['x'] }}" y1="{{ $trendTop }}" x2="{{ $point['x'] }}" y2="{{ $trendBottom }}" class="chart-hover-line"></line>
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5.8" class="chart-point"></circle>
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="18" class="chart-point-target"></circle>
                        </g>
                        <text x="{{ $point['x'] }}" y="274" class="chart-x-label" text-anchor="middle">{{ $point['label'] }}</text>
                    @endforeach
                </svg>
            </div>
        </section>

        <section class="card chart-card grouped-card">
            <div class="card-head">
                <div>
                    <h2>Grouped Bar Status per Kategori</h2>
                    <small>Perbandingan status untuk kategori</small>
                </div>
            </div>
            @php
                $groupedMax = max(collect($statusByCategory)->flatten()->max() ?: 0, 1);
                $statusColors = [
                    'Draft' => 'status-draft',
                    'Review' => 'status-review',
                    'Siap Tayang' => 'status-siap-tayang',
                    'Sudah Tayang' => 'status-sudah-tayang',
                    'Diarsipkan' => 'status-diarsipkan',
                ];
            @endphp
            <div class="grouped-chart">
                <div class="grouped-legend">
                    @foreach(\App\Models\VideoArchive::STATUSES as $status)
                        <span><i class="{{ $statusColors[$status] }}"></i>{{ $status }}</span>
                    @endforeach
                </div>
                <div class="grouped-grid">
                    @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
                        <div class="grouped-group">
                            <div class="grouped-bars">
                                @foreach(\App\Models\VideoArchive::STATUSES as $status)
                                    @php
                                        $count = $statusByCategory[$category][$status] ?? 0;
                                        $height = $count > 0 ? max(8, round(($count / $groupedMax) * 100)) : 8;
                                    @endphp
                                    <div class="grouped-bar-wrap" title="{{ $category }} - {{ $status }}: {{ $count }}">
                                        <i class="{{ $statusColors[$status] }}" style="height: {{ $height }}%"></i>
                                        <span>{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <strong>{{ $category }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <section class="card activity-panel">
        <div class="card-head">
            <div>
                <h2>Log Aktivitas</h2>
                <small>Riwayat terbaru perubahan arsip</small>
            </div>
        </div>
        <div class="activity-list">
            @forelse($activities as $activity)
                @php $actorName = $activity->meta['actor'] ?? $activity->user->name; @endphp
                <div class="activity-item">
                    <span class="activity-dot"></span>
                    <div>
                        <strong>{{ ucfirst($activity->action) }} {{ $activity->title_snapshot }}</strong>
                        <small>{{ $actorName }} &middot; {{ $activity->created_at->diffForHumans() }}</small>
                    </div>
                    @if($activity->archive)
                        <a href="{{ route('archives.show', $activity->archive) }}">Detail</a>
                    @endif
                </div>
            @empty
                <p class="empty">Belum ada aktivitas.</p>
            @endforelse
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const chart = document.querySelector('[data-upload-trend]');
    const tooltip = chart?.querySelector('[data-chart-tooltip]');
    const tooltipTotal = chart?.querySelector('[data-chart-tooltip-total]');
    const tooltipLabel = chart?.querySelector('[data-chart-tooltip-label]');
    const points = chart?.querySelectorAll('.chart-hit') || [];

    if (!chart || !tooltip || !tooltipTotal || !tooltipLabel || !points.length) return;

    const showPoint = (point) => {
        const total = Number(point.dataset.total || 0);
        const label = point.dataset.label || '';
        const x = Number(point.dataset.x || 0);
        const y = Number(point.dataset.y || 0);
        const xPercent = (x / 700) * 100;
        const yPercent = (y / 300) * 100;

        points.forEach((item) => item.classList.toggle('is-active', item === point));
        tooltipTotal.textContent = `${total} upload`;
        tooltipLabel.textContent = label;
        tooltip.style.left = `${xPercent}%`;
        tooltip.style.top = `${yPercent}%`;
        tooltip.hidden = false;
    };

    const hidePoint = () => {
        points.forEach((point) => point.classList.remove('is-active'));
        tooltip.hidden = true;
    };

    points.forEach((point) => {
        point.addEventListener('mouseenter', () => showPoint(point));
        point.addEventListener('focus', () => showPoint(point));
        point.addEventListener('mouseleave', hidePoint);
        point.addEventListener('blur', hidePoint);
    });
});
</script>
@endsection
