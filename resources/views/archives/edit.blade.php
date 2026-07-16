@extends('layouts.app') @section('title','Edit Arsip') @section('content')
<div class="page-head"><div><h1>Edit Arsip</h1><p>Perbarui informasi dan file video.</p></div></div><form method="post" action="{{ route('archives.update',$archive) }}" enctype="multipart/form-data">@csrf @method('put') @include('archives._form')</form>@endsection
