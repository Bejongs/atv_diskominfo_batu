<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_admin_user(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.com',
            'role' => User::ROLE_ADMIN,
            'is_active' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'admin.baru@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_regular_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => 'nonaktif@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_super_admin_cannot_deactivate_or_demote_own_account(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)->from(route('admin.users.edit', $superAdmin))->put(route('admin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => User::ROLE_ADMIN,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.edit', $superAdmin))
            ->assertSessionHasErrors(['role']);

        $superAdmin->refresh();
        $this->assertSame(User::ROLE_SUPER_ADMIN, $superAdmin->role);
        $this->assertTrue($superAdmin->is_active);
    }
}
