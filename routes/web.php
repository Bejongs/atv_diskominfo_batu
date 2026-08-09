<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\VideoArchiveController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/profil', ProfileController::class)->name('profile');
    Route::get('/profil/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/arsip/deteksi-kategori', [VideoArchiveController::class, 'detectCategory'])->name('archives.detect-category');
    Route::get('/arsip/export', [VideoArchiveController::class, 'export'])->name('archives.export');
    Route::get('/upload', [VideoArchiveController::class, 'create'])->name('archives.upload');
    Route::get('/jadwal-tayang', ScheduleController::class)->name('schedules.index');
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/laporan/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/backup/data', BackupController::class)->name('backup.data');
    Route::resource('admin/users', UserController::class)->except(['show', 'destroy'])->names('admin.users');
    Route::post('/arsip/bulk-action', [VideoArchiveController::class, 'bulkAction'])->name('archives.bulk-action');
    Route::get('/arsip/{archive}/thumbnail', [VideoArchiveController::class, 'thumbnail'])->name('archives.thumbnail');
    Route::get('/arsip/{archive}/preview', [VideoArchiveController::class, 'preview'])->name('archives.preview');
    Route::get('/arsip/{archive}/unduh', [VideoArchiveController::class, 'download'])->name('archives.download');
    Route::resource('arsip', VideoArchiveController::class)->parameters(['arsip' => 'archive'])->names('archives');
});
