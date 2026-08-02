<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VideoArchive;
use App\Models\VideoArchiveActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecoverVideoArchivesFromStorage extends Command
{
    protected $signature = 'archives:recover-from-storage {--dry-run : Preview recovered rows without inserting them}';

    protected $description = 'Recover video archive rows from existing storage thumbnails and videos';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $user = User::query()->first();

        if (! $user) {
            $this->error('No user found. Run the database seeder first.');

            return self::FAILURE;
        }

        $thumbnails = collect($disk->files('thumbnails'))
            ->filter(fn (string $path) => Str::endsWith($path, '.svg'))
            ->sortBy(fn (string $path) => $disk->lastModified($path))
            ->values();

        $videos = collect($disk->files('videos'))
            ->filter(fn (string $path) => Str::endsWith(Str::lower($path), ['.mp4', '.mpeg', '.mov', '.avi', '.webm']))
            ->map(fn (string $path) => [
                'path' => $path,
                'timestamp' => $disk->lastModified($path),
            ])
            ->sortBy('timestamp')
            ->values();

        if ($thumbnails->isEmpty()) {
            $this->warn('No thumbnail files found in storage/app/public/thumbnails.');

            return self::SUCCESS;
        }

        $usedVideoPaths = [];
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($thumbnails as $thumbnailPath) {
            $metadata = $this->metadataFromThumbnail($disk->get($thumbnailPath));
            $video = $this->nearestVideo($thumbnailPath, $videos, $usedVideoPaths, $disk);

            if (VideoArchive::query()
                ->where('thumbnail_path', $thumbnailPath)
                ->when($video, fn ($query) => $query->orWhere('file_path', $video['path']))
                ->exists()) {
                $skippedCount++;

                continue;
            }

            $payload = [
                'user_id' => $user->id,
                'title' => $metadata['title'],
                'description' => 'Dipulihkan dari file storage setelah database ter-reset.',
                'category' => $metadata['category'],
                'issue' => $metadata['issue'],
                'age_rating' => null,
                'status' => 'Draft',
                'air_date' => null,
                'air_time' => null,
                'video_url' => null,
                'duration_minutes' => null,
                'duration_seconds' => null,
                'file_path' => $video['path'] ?? null,
                'thumbnail_path' => $thumbnailPath,
                'original_name' => $video ? basename($video['path']) : null,
                'mime_type' => $video ? ($disk->mimeType($video['path']) ?: 'video/mp4') : null,
                'file_size' => $video ? $disk->size($video['path']) : null,
            ];

            $this->line(($this->option('dry-run') ? '[preview] ' : '[recover] ').$payload['title'].' | '.$payload['category'].' | '.$payload['issue'].' | '.($payload['file_path'] ?? 'tanpa video'));

            if ($this->option('dry-run')) {
                continue;
            }

            $archive = VideoArchive::create($payload);

            VideoArchiveActivity::create([
                'video_archive_id' => $archive->id,
                'user_id' => $user->id,
                'action' => 'recovered',
                'title_snapshot' => $archive->title,
                'meta' => [
                    'source' => 'storage_files',
                    'thumbnail_path' => $thumbnailPath,
                    'file_path' => $video['path'] ?? null,
                    'note' => 'Recovered after database reset. Original schedule, status, rating, and description were unavailable.',
                ],
            ]);

            if ($video) {
                $usedVideoPaths[] = $video['path'];
            }

            $createdCount++;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database rows were inserted.');

            return self::SUCCESS;
        }

        $this->info($createdCount.' archive(s) recovered. '.$skippedCount.' existing archive(s) skipped.');

        return self::SUCCESS;
    }

    private function metadataFromThumbnail(string $svg): array
    {
        preg_match_all('/<text\b[^>]*>(.*?)<\/text>/s', $svg, $matches);

        $texts = collect($matches[1] ?? [])
            ->map(fn (string $text) => trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')))
            ->filter()
            ->reject(fn (string $text) => in_array($text, ['ATV', 'PREVIEW', 'KOVER VIDEO'], true))
            ->values();

        $categoryLine = $texts->first(fn (string $text) => str_contains($text, '·'));
        $category = 'News';
        $issue = 'Sosial';

        if ($categoryLine) {
            [$rawCategory, $rawIssue] = array_pad(array_map('trim', explode('·', $categoryLine, 2)), 2, null);
            $category = in_array($rawCategory, VideoArchive::CATEGORIES, true) ? $rawCategory : $category;
            $issue = in_array($rawIssue, VideoArchive::ISSUES, true) ? $rawIssue : $issue;
        }

        $title = $texts
            ->reject(fn (string $text) => $text === $categoryLine || in_array($text, VideoArchive::CATEGORIES, true))
            ->first() ?: 'Arsip Video Dipulihkan';

        return [
            'title' => Str::limit($title, 255, ''),
            'category' => $category,
            'issue' => $issue,
        ];
    }

    private function nearestVideo(string $thumbnailPath, $videos, array $usedVideoPaths, $disk): ?array
    {
        $thumbnailTimestamp = $disk->lastModified($thumbnailPath);

        return $videos
            ->reject(fn (array $video) => in_array($video['path'], $usedVideoPaths, true))
            ->map(function (array $video) use ($thumbnailTimestamp) {
                $video['distance'] = abs($video['timestamp'] - $thumbnailTimestamp);

                return $video;
            })
            ->filter(fn (array $video) => $video['distance'] <= 10)
            ->sortBy('distance')
            ->first();
    }
}
