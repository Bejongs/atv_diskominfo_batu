<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = ['Judul', 'Kategori', 'Issue', 'Rating Usia', 'Status', 'Durasi', 'Rencana Tayang', 'Link Video', 'Pengunggah', 'Nama File', 'Ukuran', 'Dibuat'];
$rows = [
    [
        'Selamat pagi Indonesia',
        'News',
        'Sosial',
        'Remaja (13-17 tahun)',
        'Siap Tayang',
        '8 menit',
        '28 Jul 2026, 12:01',
        'https://youtu.be/test',
        'Admin',
        'video panjang banget.mp4',
        '10 MB',
        '2026-07-28 09:08:00',
    ],
];

file_put_contents(storage_path('app/debug-export.xlsx'), App\Support\SimpleXlsxExporter::make('Laporan Arsip Video ATV', $columns, $rows));

