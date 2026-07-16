<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_user_can_upload_video_archive(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('archives.store'), [
            'title' => 'Berita Kota Batu',
            'description' => 'Materi berita harian.',
            'category' => 'News',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-20',
            'video' => UploadedFile::fake()->create('berita.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseHas('video_archives', ['title' => 'Berita Kota Batu', 'category' => 'News']);
        $path = \App\Models\VideoArchive::firstOrFail()->file_path;
        Storage::disk('public')->assertExists($path);
    }

    public function test_category_can_be_detected_from_title_and_description(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('archives.detect-category'), [
            'title' => 'Ayo Cegah Stunting',
            'description' => 'Iklan layanan masyarakat untuk menjaga kesehatan anak.',
        ])->assertOk()->assertJson(['category' => 'ILM']);

        $this->actingAs($user)->postJson(route('archives.detect-category'), [
            'title' => 'Podcast Kota Batu Episode 7',
            'description' => 'Program dialog bersama pelaku wisata.',
        ])->assertOk()->assertJson(['category' => 'Program']);
    }
}
