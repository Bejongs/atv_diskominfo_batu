<?php

namespace App\Console\Commands;

use App\Services\YouTubeFeedImporter;
use Illuminate\Console\Command;

class SyncYouTubeArchives extends Command
{
    protected $signature = 'archives:sync-youtube';

    protected $description = 'Import video terbaru dari feed channel YouTube ke arsip video.';

    public function handle(YouTubeFeedImporter $importer): int
    {
        if (! $importer->isConfigured()) {
            $this->warn('YOUTUBE_CHANNEL_ID atau YOUTUBE_FEED_URL belum diisi. Sinkron YouTube dilewati.');

            return self::SUCCESS;
        }

        $result = $importer->import();

        $this->info("YouTube sync selesai. Baru: {$result['created']}, durasi diperbarui: {$result['updated']}, dilewati: {$result['skipped']}, terbaca: {$result['total']}.");

        return self::SUCCESS;
    }
}
