@extends('layouts.app')
@section('title', 'Manajemen User')
@section('content')
<div class="page-head user-admin-head">
    <div>
        <span class="eyebrow">Super Admin</span>
        <h1>Manajemen User</h1>
        <p>Kelola akun admin, role, status aktif, dan reset password.</p>
    </div>
    <a class="btn primary user-admin-add" href="{{ route('admin.users.create') }}">+ Tambah User</a>
</div>

<section class="card user-admin-card">
    <div class="table-wrap">
        <table class="user-admin-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><strong class="user-admin-name">{{ $user->name }}</strong></td>
                        <td><span class="user-admin-email">{{ $user->email }}</span></td>
                        <td><span class="user-role-pill {{ $user->isSuperAdmin() ? 'super' : '' }}">{{ $user->roleLabel() }}</span></td>
                        <td><span class="user-status-pill {{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td class="user-admin-actions"><a href="{{ route('admin.users.edit', $user) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Belum ada akun admin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{ $users->links('pagination.simple-atv') }}
@endsection
