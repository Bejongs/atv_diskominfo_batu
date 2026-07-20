<?php

namespace Tests\Feature;

use App\Models\VideoArchive;
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
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-20',
            'video_url' => 'https://example.com/video/berita-kota-batu',
            'video' => [
                UploadedFile::fake()->create('berita-1.mp4', 1024, 'video/mp4'),
                UploadedFile::fake()->create('berita-2.mp4', 1024, 'video/mp4'),
            ],
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseCount('video_archives', 2);
        $this->assertDatabaseCount('video_archive_activities', 2);

        $archive = \App\Models\VideoArchive::firstOrFail();
        $this->assertSame('News', $archive->category);
        $this->assertSame('Ekonomi', $archive->issue);
        $this->assertSame('https://example.com/video/berita-kota-batu', $archive->video_url);
        Storage::disk('public')->assertExists($archive->file_path);
        Storage::disk('public')->assertExists($archive->thumbnail_path);
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
}
