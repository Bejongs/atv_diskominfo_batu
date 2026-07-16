<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'total' => VideoArchive::count(),
            'ready' => VideoArchive::where('status', 'Siap Tayang')->count(),
            'aired' => VideoArchive::where('status', 'Sudah Tayang')->count(),
            'size' => VideoArchive::sum('file_size'),
            'categories' => VideoArchive::selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
            'latest' => VideoArchive::with('user')->latest()->limit(5)->get(),
        ]);
    }
}
