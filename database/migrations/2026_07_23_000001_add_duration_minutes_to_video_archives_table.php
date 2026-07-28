<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};
