@extends('layouts.app')
@section('title', 'Tambah User')
@section('content')
<div class="page-head user-admin-head user-admin-form-head">
    <div>
        <span class="eyebrow">Super Admin</span>
        <h1>Tambah User</h1>
        <p>Buat akun admin baru untuk mengelola arsip.</p>
    </div>
    <a class="btn user-admin-back" href="{{ route('admin.users.index') }}">Kembali</a>
</div>

@include('admin.users.form', ['user' => null])
@endsection
