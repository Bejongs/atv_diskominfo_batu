<?php

namespace Tests\Feature;

use App\Models\VideoArchive;
use App\Models\VideoArchiveActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_bulk_upload_video_archive(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('archives.store'), [
            'title' => 'Berita Kota Batu',
            'description' => 'Materi berita harian.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'age_rating' => 'R',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-20',
            'video_url' => 'https://example.com/video/berita-kota-batu',
            'duration_seconds_per_file' => [754, 5125],
            'video' => [
                UploadedFile::fake()->create('berita-1.mp4', 1024, 'video/mp4'),
                UploadedFile::fake()->create('berita-2.mp4', 1024, 'video/mp4'),
            ],
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseCount('video_archives', 2);
        $this->assertDatabaseCount('video_archive_activities', 2);

        $archive = \App\Models\VideoArchive::orderBy('id')->firstOrFail();
        $this->assertSame('News', $archive->category);
        $this->assertSame('Ekonomi', $archive->issue);
        $this->assertSame('R', $archive->age_rating);
        $this->assertSame('Remaja (13-17 tahun)', $archive->age_rating_label);
        $this->assertSame('https://example.com/video/berita-kota-batu', $archive->video_url);
        $this->assertSame(754, $archive->duration_seconds);
        $this->assertSame(13, $archive->duration_minutes);
        $this->assertSame('12 menit 34 detik', $archive->formatted_duration);
        $this->assertSame(5125, VideoArchive::orderBy('id')->skip(1)->firstOrFail()->duration_seconds);
        Storage::disk('public')->assertExists($archive->file_path);
        Storage::disk('public')->assertExists($archive->thumbnail_path);
    }

    public function test_user_can_open_schedule_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Jadwal Tayang')
            ->assertSee('Kalender')
            ->assertSee('Tayang Hari Ini');
    }

    public function test_user_can_create_video_archive_without_uploading_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('archives.store'), [
            'title' => 'Link Berita Kota Batu',
            'description' => 'Materi berita dari link eksternal.',
            'category' => 'News',
            'issue' => 'Sosial',
            'age_rating' => 'A',
            'status' => 'Draft',
            'air_date' => '2026-07-21',
            'video_url' => 'https://example.com/video/link-berita',
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseHas('video_archives', [
            'title' => 'Link Berita Kota Batu',
            'video_url' => 'https://example.com/video/link-berita',
            'age_rating' => 'A',
            'file_path' => null,
            'original_name' => null,
        ]);

        $archive = VideoArchive::where('title', 'Link Berita Kota Batu')->firstOrFail();
        $this->assertSame('Tidak ada file', $archive->formatted_size);
        Storage::disk('public')->assertExists($archive->thumbnail_path);
    }

    public function test_user_can_change_status_for_selected_archives(): void
    {
        $user = User::factory()->create();
        $first = $this->createArchiveForBulkAction($user, ['title' => 'Arsip Pilihan 1']);
        $second = $this->createArchiveForBulkAction($user, ['title' => 'Arsip Pilihan 2']);

        $response = $this->actingAs($user)->post(route('archives.bulk-action'), [
            'selected' => [$first->id, $second->id],
            'action' => 'change_status',
            'status' => 'Review',
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseHas('video_archives', ['id' => $first->id, 'status' => 'Review']);
        $this->assertDatabaseHas('video_archives', ['id' => $second->id, 'status' => 'Review']);
    }

    public function test_user_can_delete_selected_archives(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $first = $this->createArchiveForBulkAction($user, ['title' => 'Arsip Hapus 1']);
        $second = $this->createArchiveForBulkAction($user, ['title' => 'Arsip Hapus 2']);

        $response = $this->actingAs($user)->post(route('archives.bulk-action'), [
            'selected' => [$first->id, $second->id],
            'action' => 'delete',
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseMissing('video_archives', ['id' => $first->id]);
        $this->assertDatabaseMissing('video_archives', ['id' => $second->id]);
    }

    public function test_user_can_export_filtered_video_archives_to_xlsx_and_pdf(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('archives.store'), [
            'title' => 'Berita Kota Batu',
            'description' => 'Materi berita harian.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-20',
            'video_url' => 'https://example.com/video/export-berita',
            'video' => [
                UploadedFile::fake()->create('berita.mp4', 1024, 'video/mp4'),
            ],
        ]);

        $xlsxResponse = $this->actingAs($user)->get(route('archives.export', ['category' => 'News', 'format' => 'xlsx']));

        $xlsxResponse->assertOk();
        $xlsxResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $xlsxResponse->getContent());

        $pdfResponse = $this->actingAs($user)->get(route('archives.export', ['category' => 'News', 'format' => 'pdf']));

        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
        $this->assertStringContainsString('Berita Kota Batu', $pdfResponse->getContent());
    }

    public function test_archive_update_logs_field_level_changes(): void
    {
        $user = User::factory()->create();
        $archive = VideoArchive::create([
            'user_id' => $user->id,
            'title' => 'Arsip Lama',
            'description' => 'Deskripsi arsip lama.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'age_rating' => 'SU',
            'status' => 'Draft',
            'air_date' => '2026-07-20',
            'air_time' => '08:00',
            'video_url' => 'https://example.com/video-lama',
            'duration_seconds' => 600,
            'duration_minutes' => 10,
        ]);

        $response = $this->actingAs($user)->put(route('archives.update', $archive), [
            'title' => 'Arsip Baru',
            'description' => $archive->description,
            'category' => $archive->category,
            'issue' => $archive->issue,
            'age_rating' => $archive->age_rating,
            'status' => 'Review',
            'air_date' => '2026-07-21',
            'air_time' => '09:30',
            'video_url' => $archive->video_url,
        ]);

        $response->assertRedirect(route('archives.show', $archive));

        $activity = VideoArchiveActivity::where('video_archive_id', $archive->id)->where('action', 'updated')->latest()->firstOrFail();
        $this->assertNotEmpty($activity->meta['changes']);
        $statusChange = collect($activity->meta['changes'])->firstWhere('field', 'status');
        $this->assertNotNull($statusChange);
        $this->assertSame('Status', $statusChange['label']);
        $this->assertSame('Draft', $statusChange['before']);
        $this->assertSame('Review', $statusChange['after']);
    }

    public function test_thumbnail_route_returns_svg_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('archives.store'), [
            'title' => 'Berita Kota Batu',
            'description' => 'Materi berita harian.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-20',
            'video' => [
                UploadedFile::fake()->create('berita.mp4', 1024, 'video/mp4'),
            ],
        ]);

        $archive = VideoArchive::firstOrFail();

        $response = $this->actingAs($user)->get(route('archives.thumbnail', $archive));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/svg+xml');
    }

    public function test_category_can_be_detected_from_title_and_description(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('archives.detect-category'), [
            'title' => 'Ayo Cegah Stunting',
            'description' => 'Iklan layanan masyarakat untuk menjaga kesehatan anak.',
        ])->assertOk()->assertJson(['category' => 'Iklan Layanan Masyarakat']);

        $this->actingAs($user)->postJson(route('archives.detect-category'), [
            'title' => 'Podcast Kota Batu Episode 7',
            'description' => 'Program dialog bersama pelaku wisata.',
        ])->assertOk()->assertJson(['category' => 'Program']);
    }

    public function test_due_siap_tayang_archive_is_auto_updated_to_sudah_tayang(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 08:00:00'));

        $user = User::factory()->create();
        $archive = VideoArchive::create([
            'user_id' => $user->id,
            'title' => 'Arsip Siar',
            'description' => 'Konten yang sudah tayang.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-16',
            'file_path' => 'videos/test.mp4',
            'thumbnail_path' => null,
            'original_name' => 'test.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 1024,
        ]);

        Artisan::call('archives:sync-statuses');

        $this->assertDatabaseHas('video_archives', [
            'id' => $archive->id,
            'status' => 'Sudah Tayang',
        ]);

        $this->assertDatabaseHas('video_archive_activities', [
            'video_archive_id' => $archive->id,
            'action' => 'auto_status_updated',
        ]);

        Carbon::setTestNow();
    }

    public function test_due_siap_tayang_archive_is_auto_updated_when_archive_page_is_opened(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-17 08:00:00'));

        $user = User::factory()->create();
        $archive = VideoArchive::create([
            'user_id' => $user->id,
            'title' => 'Arsip Hari Ini',
            'description' => 'Konten siap tayang hari ini.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-17',
            'file_path' => 'videos/test.mp4',
            'thumbnail_path' => null,
            'original_name' => 'test.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 1024,
        ]);

        $this->actingAs($user)->get(route('archives.index'))->assertOk();

        $this->assertDatabaseHas('video_archives', [
            'id' => $archive->id,
            'status' => 'Sudah Tayang',
        ]);

        $this->assertDatabaseHas('video_archive_activities', [
            'video_archive_id' => $archive->id,
            'action' => 'auto_status_updated',
        ]);

        Carbon::setTestNow();
    }

    private function createArchiveForBulkAction(User $user, array $overrides = []): VideoArchive
    {
        return VideoArchive::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Arsip Pilihan',
            'description' => 'Konten pilihan.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Draft',
            'air_date' => '2026-07-20',
            'file_path' => null,
            'thumbnail_path' => null,
            'original_name' => null,
            'mime_type' => null,
            'file_size' => null,
        ], $overrides));
    }
}
