<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_loads(): void
    {
        $user = User::factory()->create();

        VideoArchive::create([
            'user_id' => $user->id,
            'title' => 'Filter Dashboard',
            'category' => 'Program',
            'issue' => 'Ekonomi',
            'status' => 'Draft',
            'created_at' => '2026-08-08 08:00:00',
            'updated_at' => '2026-08-08 08:00:00',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan arsip tayangan ATV hari ini')
            ->assertSee('Filter Dashboard');
    }

    public function test_super_admin_can_download_backup_json(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)->get(route('backup.data'))
            ->assertOk()
            ->assertHeader('content-disposition')
            ->assertJsonStructure(['generated_at', 'users', 'video_archives', 'video_archive_activities']);
    }

    public function test_regular_admin_cannot_download_backup_json(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('backup.data'))->assertForbidden();
    }
}
