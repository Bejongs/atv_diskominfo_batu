@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<div class="page-head user-admin-head user-admin-form-head">
    <div>
        <span class="eyebrow">Super Admin</span>
        <h1>Edit User</h1>
        <p>Ubah role, status akun, atau reset password.</p>
    </div>
    <a class="btn user-admin-back" href="{{ route('admin.users.index') }}">Kembali</a>
</div>

@include('admin.users.form', ['user' => $user])
@endsection
