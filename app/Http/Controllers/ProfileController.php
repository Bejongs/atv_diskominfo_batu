<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui.');
    }

    public function __invoke(Request $request)
    {
        $user = $request->user();

        $recentArchives = VideoArchive::with('user')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->get();

        $stats = [
            'total_archives' => $user->videoArchives()->count(),
            'ready' => $user->videoArchives()->where('status', 'Siap Tayang')->count(),
            'aired' => $user->videoArchives()->where('status', 'Sudah Tayang')->count(),
            'draft' => $user->videoArchives()->where('status', 'Draft')->count(),
        ];

        return view('profile.index', compact('user', 'recentArchives', 'stats'));
    }
}
