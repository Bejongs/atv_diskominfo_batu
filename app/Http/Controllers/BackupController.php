<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VideoArchive;
use App\Models\VideoArchiveActivity;

class BackupController extends Controller
{
    public function __invoke()
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'users' => User::orderBy('id')->get(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at']),
            'video_archives' => VideoArchive::with('user:id,name,email')->orderBy('id')->get(),
            'video_archive_activities' => VideoArchiveActivity::with('user:id,name,email')->orderBy('id')->get(),
        ];

        $filename = 'backup-atv-'.now()->format('Ymd_His').'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
