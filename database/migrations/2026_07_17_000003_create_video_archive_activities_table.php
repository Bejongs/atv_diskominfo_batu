<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_archive_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_archive_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('title_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_archive_activities');
    }
};
