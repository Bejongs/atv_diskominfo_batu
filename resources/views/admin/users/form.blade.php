@php($isEdit = filled($user))
<form class="card form-card user-admin-form" method="post" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf
    @if($isEdit)
        @method('put')
    @endif

    <div class="columns">
        <label>Nama
            <input type="text" name="name" value="{{ old('name', $user?->name) }}" required>
            @error('name')<small class="error">{{ $message }}</small>@enderror
        </label>
        <label>Email
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
            @error('email')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="columns">
        <label>Role
            <select name="role" required>
                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role', $user?->role ?? \App\Models\User::ROLE_ADMIN) === \App\Models\User::ROLE_ADMIN)>Admin</option>
                <option value="{{ \App\Models\User::ROLE_SUPER_ADMIN }}" @selected(old('role', $user?->role) === \App\Models\User::ROLE_SUPER_ADMIN)>Super Admin</option>
            </select>
            @error('role')<small class="error">{{ $message }}</small>@enderror
        </label>
        <label class="check user-active-check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
            <span>Akun aktif</span>
        </label>
    </div>

    <div class="columns">
        <label>Password {{ $isEdit ? 'baru' : '' }}
            <input type="password" name="password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
            @error('password')<small class="error">{{ $message }}</small>@enderror
        </label>
        <label>Konfirmasi password
            <input type="password" name="password_confirmation" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
        </label>
    </div>

    <div class="form-actions">
        <a class="btn user-admin-back" href="{{ route('admin.users.index') }}">Batal</a>
        <button class="btn primary">Simpan</button>
    </div>
</form>
