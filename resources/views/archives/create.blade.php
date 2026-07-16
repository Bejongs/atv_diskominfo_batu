@extends('layouts.app') @section('title','Upload Video') @section('content')
<div class="page-head"><div><h1>Upload Video</h1><p>Tambahkan materi tayangan baru ke arsip.</p></div></div><form method="post" action="{{ route('archives.store') }}" enctype="multipart/form-data">@csrf @include('archives._form')</form>@endsection
