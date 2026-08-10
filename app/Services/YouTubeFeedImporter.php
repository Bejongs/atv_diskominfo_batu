<?php

namespace App\Services;

use App\Models\User;
use App\Models\VideoArchive;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

class YouTubeFeedImporter
{
    public function __construct(private readonly CategoryDetector $detector)
    {
    }

    public function import(?int $actorUserId = null): array
    {
        $feedUrl = $this->feedUrl();
        $actor = $this->resolveActor($actorUserId);

        $response = Http::timeout(15)->get($feedUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Feed YouTube gagal diambil. Status HTTP: '.$response->status());
        }

        $entries = $this->parseEntries($response->body());
        $maxImport = max(1, (int) config('services.youtube.max_import', 15));
        $created = 0;
        $skipped = 0;
        $updated = 0;

        foreach (array_slice($entries, 0, $maxImport) as $entry) {
            $durationSeconds = $this->fetchDurationSeconds($entry['video_id']);
            $durationMinutes = $durationSeconds ? (int) ceil($durationSeconds / 60) : null;
            $archive = VideoArchive::where('external_id', $entry['video_id'])->first();

            if ($archive) {
                if (! $archive->duration_seconds && $durationSeconds) {
                    $archive->update([
                        'duration_seconds' => $durationSeconds,
                        'duration_minutes' => $durationMinutes,
                    ]);
                    $updated++;
                }

                $skipped++;

                continue;
            }

            $detected = $this->detector->detect($entry['title'], $entry['description']);

            DB::transaction(function () use ($entry, $actor, $detected, $durationSeconds, $durationMinutes): void {
                VideoArchive::create([
                    'user_id' => $actor->id,
                    'source' => 'youtube',
                    'external_id' => $entry['video_id'],
                    'external_thumbnail_url' => $entry['thumbnail_url'],
                    'external_published_at' => $entry['published_at'],
                    'title' => $entry['title'],
                    'description' => $entry['description'],
                    'category' => $detected['category'],
                    'issue' => $detected['issue'],
                    'status' => 'Sudah Tayang',
                    'air_date' => $entry['published_at']?->toDateString(),
                    'video_url' => $entry['url'],
                    'duration_seconds' => $durationSeconds,
                    'duration_minutes' => $durationMinutes,
                    'thumbnail_path' => $this->storeThumbnail($entry['title'], $detected['category'], $detected['issue']),
                    'original_name' => 'YouTube',
                ]);
            });

            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'updated' => $updated,
            'total' => count($entries),
        ];
    }

    public function isConfigured(): bool
    {
        return filled(config('services.youtube.feed_url')) || filled(config('services.youtube.channel_id'));
    }

    private function feedUrl(): string
    {
        $feedUrl = config('services.youtube.feed_url');

        if (filled($feedUrl)) {
            return (string) $feedUrl;
        }

        $channelId = config('services.youtube.channel_id');

        if (blank($channelId)) {
            throw new RuntimeException('YOUTUBE_CHANNEL_ID atau YOUTUBE_FEED_URL belum diisi.');
        }

        return 'https://www.youtube.com/feeds/videos.xml?channel_id='.urlencode((string) $channelId);
    }

    private function resolveActor(?int $actorUserId): User
    {
        if ($actorUserId) {
            return User::findOrFail($actorUserId);
        }

        $configuredUserId = config('services.youtube.import_user_id');

        if (filled($configuredUserId)) {
            return User::findOrFail((int) $configuredUserId);
        }

        return User::orderBy('id')->firstOrFail();
    }

    private function parseEntries(string $xml): array
    {
        $feed = simplexml_load_string($xml);

        if (! $feed instanceof SimpleXMLElement) {
            throw new RuntimeException('Format feed YouTube tidak valid.');
        }

        $entries = [];

        foreach ($feed->entry ?? [] as $entry) {
            $media = $entry->children('media', true);
            $videoId = trim((string) $entry->children('yt', true)->videoId);

            if ($videoId === '') {
                continue;
            }

            $thumbnailAttributes = $media->group->thumbnail?->attributes();
            $description = trim((string) ($media->group->description ?? $entry->summary ?? ''));

            $entries[] = [
                'video_id' => $videoId,
                'title' => Str::limit(trim((string) $entry->title), 255, ''),
                'description' => $description,
                'url' => 'https://www.youtube.com/watch?v='.$videoId,
                'thumbnail_url' => $thumbnailAttributes ? (string) $thumbnailAttributes['url'] : null,
                'published_at' => filled((string) $entry->published) ? Carbon::parse((string) $entry->published) : null,
            ];
        }

        return $entries;
    }

    private function fetchDurationSeconds(string $videoId): ?int
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; ATVArchive/1.0)',
                ])
                ->get('https://www.youtube.com/watch', ['v' => $videoId]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        if (! preg_match('/"lengthSeconds":"(\d+)"/', $response->body(), $matches)) {
            return null;
        }

        return max(1, (int) $matches[1]);
    }

    private function storeThumbnail(string $title, string $category, string $issue): ?string
    {
        $safeTitle = e(Str::limit($title, 52));
        $safeCategory = e($category);
        $safeIssue = e($issue);
        $path = 'thumbnails/'.Str::uuid().'.svg';
        $thumbnail = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360" fill="none">
  <rect width="640" height="360" rx="28" fill="#111827"/>
  <rect x="42" y="42" width="556" height="276" rx="22" fill="#1f2937" stroke="#ef4444" stroke-width="4"/>
  <path d="M292 145l86 50-86 50z" fill="#ef4444"/>
  <text x="56" y="86" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="20" font-weight="800">YOUTUBE</text>
  <text x="56" y="270" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="30" font-weight="800">$safeTitle</text>
  <text x="56" y="304" fill="#d1d5db" font-family="Inter, Arial, sans-serif" font-size="16">$safeCategory - $safeIssue</text>
</svg>
SVG;

        Storage::disk('public')->put($path, $thumbnail);

        return $path;
    }
}
