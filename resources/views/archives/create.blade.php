@extends('layouts.app')
@section('title','Upload Video')
@section('content')
<form class="upload-form-shell" method="post" action="{{ route('archives.store') }}" enctype="multipart/form-data">
    @csrf
    @include('archives._form')
</form>
@endsection
