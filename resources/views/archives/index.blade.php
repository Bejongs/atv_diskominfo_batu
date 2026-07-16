@extends('layouts.app')
@section('title','Arsip Video')
@section('content')
<div class="page-head"><div><h1>Arsip Video</h1><p>Kelola seluruh materi tayangan ATV.</p></div><a class="btn primary" href="{{ route('archives.create') }}">＋ Upload Video</a></div>
<form class="filters card" method="get">
    <input name="search" value="{{ request('search') }}" placeholder="Cari judul atau deskripsi...">
    <div class="category-filter" aria-label="Filter kategori">
        <button type="submit" name="category" value="" class="category-button {{ request('category') ? '' : 'active' }}">Semua</button>
        @foreach(\App\Models\VideoArchive::CATEGORIES as $c)
            <button type="submit" name="category" value="{{ $c }}" class="category-button {{ request('category') === $c ? 'active' : '' }}">{{ $c }}</button>
        @endforeach
    </div>
    <select name="status"><option value="">Semua status</option>@foreach(\App\Models\VideoArchive::STATUSES as $s)<option @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select>
    <input type="date" name="date_from" value="{{ request('date_from') }}">
    <input type="date" name="date_to" value="{{ request('date_to') }}">
    <button class="btn primary">Filter</button><a class="btn" href="{{ route('archives.index') }}">Reset</a>
</form>
<div class="card table-wrap"><table><thead><tr><th>Video</th><th>Kategori</th><th>Status</th><th>Rencana Tayang</th><th>Ukuran</th><th></th></tr></thead><tbody>
@forelse($archives as $item)<tr><td><a class="title-link" href="{{ route('archives.show',$item) }}">{{ $item->title }}</a><small>{{ $item->original_name }}</small></td><td><span class="badge category">{{ $item->category }}</span></td><td><span class="badge status-{{ str($item->status)->slug() }}">{{ $item->status }}</span></td><td>{{ $item->air_date?->format('d M Y') ?? '—' }}</td><td>{{ $item->formatted_size }}</td><td class="actions"><a href="{{ route('archives.show',$item) }}">Detail</a><a href="{{ route('archives.edit',$item) }}">Edit</a></td></tr>@empty<tr><td colspan="6" class="empty">Arsip tidak ditemukan.</td></tr>@endforelse
</tbody></table></div>{{ $archives->links('pagination::simple-default') }}
@endsection
