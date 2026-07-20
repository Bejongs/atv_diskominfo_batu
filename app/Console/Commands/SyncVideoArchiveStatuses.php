<?php

namespace App\Console\Commands;

use App\Models\VideoArchive;
use App\Models\VideoArchiveActivity;
use Illuminate\Console\Command;

class SyncVideoArchiveStatuses extends Command
{
    protected $signature = 'archives:sync-statuses';

    protected $description = 'Auto update archives with due air_date to Sudah Tayang';

    public function handle(): int
    {
        $archives = VideoArchive::query()
            ->where('status', 'Siap Tayang')
            ->whereNotNull('air_date')
            ->whereDate('air_date', '<=', today())
            ->get();

        $updatedCount = 0;

        foreach ($archives as $archive) {
            $archive->update(['status' => 'Sudah Tayang']);

            VideoArchiveActivity::create([
                'video_archive_id' => $archive->id,
                'user_id' => $archive->user_id,
                'action' => 'auto_status_updated',
                'title_snapshot' => $archive->title,
                'meta' => [
                    'actor' => 'Sistem',
                    'from' => 'Siap Tayang',
                    'to' => 'Sudah Tayang',
                    'reason' => 'air_date_reached',
                ],
            ]);

            $updatedCount++;
        }

        $this->info($updatedCount.' archive(s) updated.');

        return self::SUCCESS;
    }
}
