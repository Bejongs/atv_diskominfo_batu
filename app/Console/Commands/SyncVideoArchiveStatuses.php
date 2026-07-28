<?php

namespace App\Console\Commands;

use App\Services\VideoArchiveStatusSyncer;
use Illuminate\Console\Command;

class SyncVideoArchiveStatuses extends Command
{
    protected $signature = 'archives:sync-statuses';

    protected $description = 'Auto update archives with due air_date to Sudah Tayang';

    public function handle(VideoArchiveStatusSyncer $syncer): int
    {
        $updatedCount = $syncer->syncDueToAired();

        $this->info($updatedCount.' archive(s) updated.');

        return self::SUCCESS;
    }
}
