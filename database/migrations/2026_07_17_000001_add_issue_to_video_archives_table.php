<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->string('issue', 30)->nullable()->after('category')->index();
        });
    }

    public function down(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->dropColumn('issue');
        });
    }
};
