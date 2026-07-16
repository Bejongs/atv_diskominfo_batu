@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="page-head"><div><h1>Dashboard</h1><p>Ringkasan arsip tayangan ATV hari ini.</p></div><a class="btn primary" href="{{ route('archives.create') }}">＋ Upload Video</a></div>
<div class="stats">
<div class="stat"><span>Total Video</span><strong>{{ $total }}</strong><small>Semua arsip</small></div>
<div class="stat orange"><span>Siap Tayang</span><strong>{{ $ready }}</strong><small>Menunggu ditayangkan</small></div>
<div class="stat green"><span>Sudah Tayang</span><strong>{{ $aired }}</strong><small>Video selesai tayang</small></div>
<div class="stat purple"><span>Penyimpanan</span><strong>{{ number_format($size / 1048576, 1) }} MB</strong><small>Total ukuran file</small></div>
</div>
<div class="grid-2"><section class="card"><div class="card-head"><h2>Arsip terbaru</h2><a href="{{ route('archives.index') }}">Lihat semua</a></div>
@forelse($latest as $item)<a class="recent" href="{{ route('archives.show',$item) }}"><div class="video-icon">▶</div><div><strong>{{ $item->title }}</strong><small>{{ $item->category }} · {{ $item->created_at->diffForHumans() }}</small></div><span class="badge">{{ $item->status }}</span></a>@empty<p class="empty">Belum ada video. Mulai dengan mengunggah arsip pertama.</p>@endforelse
</section><section class="card"><div class="card-head"><h2>Kategori</h2></div>
@foreach(\App\Models\VideoArchive::CATEGORIES as $category)<div class="category-row"><span>{{ $category }}</span><strong>{{ $categories[$category] ?? 0 }}</strong><div class="bar"><i style="width:{{ $total ? (($categories[$category] ?? 0)/$total*100) : 0 }}%"></i></div></div>@endforeach
</section></div>
@endsection
