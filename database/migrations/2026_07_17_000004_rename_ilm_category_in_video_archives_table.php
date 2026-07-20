<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('video_archives')
            ->where('category', 'ILM')
            ->update(['category' => 'Iklan Layanan Masyarakat']);
    }

    public function down(): void
    {
        DB::table('video_archives')
            ->where('category', 'Iklan Layanan Masyarakat')
            ->update(['category' => 'ILM']);
    }
};
