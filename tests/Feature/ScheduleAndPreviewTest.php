<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleAndPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_page_shows_conflicting_air_times(): void
    {
        $user = User::factory()->create();

        foreach (['Pertama', 'Kedua'] as $title) {
            VideoArchive::create([
                'user_id' => $user->id,
                'title' => $title,
                'category' => 'News',
                'issue' => 'Sosial',
                'status' => 'Siap Tayang',
                'air_date' => '2026-08-08',
                'air_time' => '09:00',
            ]);
        }

        $this->actingAs($user)->get(route('schedules.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Peringatan Jadwal Bentrok')
            ->assertSee('2 arsip terjadwal');
    }

    public function test_archive_detail_embeds_supported_video_link(): void
    {
        $user = User::factory()->create();
        $archive = VideoArchive::create([
            'user_id' => $user->id,
            'title' => 'Preview YouTube',
            'category' => 'Program',
            'issue' => 'Ekonomi',
            'status' => 'Draft',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $this->actingAs($user)->get(route('archives.show', $archive))
            ->assertOk()
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ');
    }
}
