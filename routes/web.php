<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
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
    Route::post('/arsip/deteksi-kategori', [VideoArchiveController::class, 'detectCategory'])->name('archives.detect-category');
    Route::get('/arsip/{archive}/unduh', [VideoArchiveController::class, 'download'])->name('archives.download');
    Route::resource('arsip', VideoArchiveController::class)->parameters(['arsip' => 'archive'])->names('archives');
});
