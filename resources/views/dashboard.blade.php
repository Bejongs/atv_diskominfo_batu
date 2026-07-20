@extends('layouts.app')
@section('title','Dashboard')
@section('content')
@php
    $trendMax = max(collect($uploadTrend)->max('total') ?: 0, 1);
    $trendPlotWidth = 620;
    $trendPlotHeight = 180;
    $trendLeft = 40;
    $trendTop = 26;
    $trendStep = count($uploadTrend) > 1 ? $trendPlotWidth / (count($uploadTrend) - 1) : 0;
    $trendPoints = collect($uploadTrend)->values()->map(function ($day, $index) use ($trendLeft, $trendTop, $trendPlotHeight, $trendStep, $trendMax) {
        $x = $trendLeft + ($trendStep * $index);
        $y = $trendTop + $trendPlotHeight - (($day['total'] / $trendMax) * $trendPlotHeight);

        return [
            'x' => $x,
            'y' => $y,
            'label' => $day['label'],
            'total' => $day['total'],
        ];
    });
    $trendPolyline = $trendPoints->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
@endphp
<div class="page-head dashboard-head">
    <div>
        <span class="eyebrow">Overview</span>
        <h1>Dashboard</h1>
        <p>Ringkasan arsip tayangan ATV hari ini.</p>
    </div>
    <a class="btn primary" href="{{ route('archives.upload') }}">&#43; Upload Video</a>
</div>

<div class="stats">
    <div class="stat"><span>Total Video</span><strong>{{ $total }}</strong><small>Semua arsip</small></div>
    <div class="stat orange"><span>Siap Tayang</span><strong>{{ $ready }}</strong><small>Menunggu ditayangkan</small></div>
    <div class="stat green"><span>Sudah Tayang</span><strong>{{ $aired }}</strong><small>Video selesai tayang</small></div>
    <div class="stat purple"><span>Penyimpanan</span><strong>{{ number_format($size / 1048576, 1) }} MB</strong><small>Total ukuran file</small></div>
</div>

<div class="grid-2 dashboard-top-grid">
    <section class="card dashboard-card">
        <div class="card-head"><h2>Arsip terbaru</h2><a href="{{ route('archives.index') }}">Lihat semua</a></div>
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
        @foreach(\App\Models\VideoArchive::CATEGORIES as $category)
            <div class="category-row">
                <span>{{ $category }}</span>
                <strong>{{ $categories[$category] ?? 0 }}</strong>
                <div class="bar"><i style="width:{{ $total ? (($categories[$category] ?? 0)/$total*100) : 0 }}%"></i></div>
            </div>
        @endforeach
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
                <small>Jumlah arsip masuk dalam 7 hari terakhir</small>
            </div>
        </div>
        <div class="line-chart-shell">
            <svg class="line-chart-svg" viewBox="0 0 700 300" preserveAspectRatio="none" role="img" aria-label="Trend statistik upload">
                @for($i = 0; $i <= 4; $i++)
                    @php
                        $gridValue = round($trendMax - (($trendMax / 4) * $i));
                        $gridY = 26 + (180 / 4) * $i;
                    @endphp
                    <line x1="40" y1="{{ $gridY }}" x2="660" y2="{{ $gridY }}" class="chart-grid"></line>
                    <text x="8" y="{{ $gridY + 4 }}" class="chart-axis-label">{{ $gridValue }}</text>
                @endfor

                @if($trendPoints->count() > 1)
                    <polyline points="{{ $trendPolyline }}" class="chart-line"></polyline>
                @endif

                @foreach($trendPoints as $point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5.5" class="chart-point"></circle>
                    <text x="{{ $point['x'] }}" y="272" class="chart-x-label" text-anchor="middle">{{ $point['label'] }}</text>
                    <text x="{{ $point['x'] }}" y="{{ $point['y'] - 14 }}" class="chart-value" text-anchor="middle">{{ $point['total'] }}</text>
                @endforeach
            </svg>
        </div>
    </section>

    <section class="card chart-card grouped-card">
        <div class="card-head">
            <div>
                <h2>Grouped Bar Status per Kategori</h2>
                <small>Perbandingan status untuk News, Iklan Layanan Masyarakat, dan Program</small>
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
@endsection

