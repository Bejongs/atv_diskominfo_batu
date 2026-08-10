<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->string('source', 30)->default('manual')->after('user_id')->index();
            $table->string('external_id')->nullable()->after('source')->unique();
            $table->string('external_thumbnail_url', 2048)->nullable()->after('thumbnail_path');
            $table->timestamp('external_published_at')->nullable()->after('external_thumbnail_url')->index();
        });
    }

    public function down(): void
    {
        Schema::table('video_archives', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn([
                'source',
                'external_id',
                'external_thumbnail_url',
                'external_published_at',
            ]);
        });
    }
};
