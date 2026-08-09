<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_page_uses_custom_view(): void
    {
        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }

    public function test_forbidden_page_uses_custom_view(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $archive = VideoArchive::create([
            'user_id' => $user->id,
            'title' => 'Arsip Terlarang',
            'description' => 'Konten tidak boleh dihapus admin biasa.',
            'category' => 'News',
            'issue' => 'Sosial',
            'status' => 'Draft',
        ]);

        $this->actingAs($user)->delete(route('archives.destroy', $archive))
            ->assertForbidden()
            ->assertSee('Akses ditolak');
    }
}
