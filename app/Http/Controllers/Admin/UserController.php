<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        return view('admin.users.index', [
            'users' => User::query()->latest()->paginate(12),
        ]);
    }

    public function create()
    {
        $this->authorizeSuperAdmin();

        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorizeSuperAdmin();

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate($this->rules($user));
        $data['is_active'] = $request->boolean('is_active');

        if ($user->is(auth()->user()) && (! $data['is_active'] || $data['role'] !== User::ROLE_SUPER_ADMIN)) {
            return back()->withErrors([
                'role' => 'Akun super admin yang sedang digunakan tidak boleh dinonaktifkan atau diturunkan rolenya.',
            ])->withInput();
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil diperbarui.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
