@extends('layouts.app')
@section('title', 'Edit Profil')
@section('content')
<div class="profile-page">
    <div class="page-head profile-edit-head">
        <div>
            <span class="eyebrow">Profil</span>
            <h1>Edit Profil</h1>
            <p>Ubah nama, email, atau password akun Anda.</p>
        </div>
        <a class="btn profile-back-btn" href="{{ route('profile') }}">Kembali</a>
    </div>

    <form class="card profile-edit-card" method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('put')

        <div class="profile-edit-section-head">
            <div>
                <h2>Informasi Akun</h2>
                <small>Data utama yang tampil di sistem arsip ATV</small>
            </div>
        </div>

        <div class="profile-edit-grid">
            <label>
                <span>Username</span>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <div class="profile-password-box">
            <div class="card-head">
                <div>
                    <h2>Password</h2>
                    <small>Isi hanya jika ingin mengganti password</small>
                </div>
            </div>

            <div class="profile-edit-grid">
                <label class="profile-password-full">
                    <span>Password saat ini</span>
                    <input type="password" name="current_password" autocomplete="current-password" placeholder="Wajib diisi jika password diubah">
                    @error('current_password')<small class="error">{{ $message }}</small>@enderror
                </label>

                <label>
                    <span>Password baru</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tidak berubah">
                    @error('password')<small class="error">{{ $message }}</small>@enderror
                </label>

                <label>
                    <span>Konfirmasi password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru">
                </label>
            </div>
        </div>

        <div class="profile-edit-actions">
            <a class="btn" href="{{ route('profile') }}">Batal</a>
            <button class="btn primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
