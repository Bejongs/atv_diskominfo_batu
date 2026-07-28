<?php

namespace App\Services;

use App\Models\VideoArchive;
use App\Models\VideoArchiveActivity;

class VideoArchiveStatusSyncer
{
    public function syncDueToAired(): int
    {
        $archives = VideoArchive::query()
            ->where('status', 'Siap Tayang')
            ->whereNotNull('air_date')
            ->where(function ($query): void {
                $query->whereDate('air_date', '<', today())
                    ->orWhere(function ($query): void {
                        $query->whereDate('air_date', today())
                            ->where(function ($query): void {
                                $query->whereNull('air_time')
                                    ->orWhere('air_time', '<=', now()->format('H:i:s'));
                            });
                    });
            })
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
                    'reason' => 'air_schedule_reached',
                ],
            ]);

            $updatedCount++;
        }

        return $updatedCount;
    }
}
