<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        $statusProgress = [
            'Draft' => 20,
            'Review' => 45,
            'Siap Tayang' => 75,
            'Sudah Tayang' => 100,
            'Diarsipkan' => 100,
        ];

        $latestArchives = VideoArchive::latest()
            ->limit(3)
            ->get(['id', 'title', 'category', 'status'])
            ->map(function (VideoArchive $archive) use ($statusProgress) {
                $archive->progress = $statusProgress[$archive->status] ?? 0;

                return $archive;
            });

        $loginStats = [
            'total' => VideoArchive::count(),
            'ready' => VideoArchive::where('status', 'Siap Tayang')->count(),
            'aired' => VideoArchive::where('status', 'Sudah Tayang')->count(),
        ];

        return view('auth.login', compact('latestArchives', 'loginStats'));
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        }

        if (! $request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun ini sedang dinonaktifkan. Hubungi super admin.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
