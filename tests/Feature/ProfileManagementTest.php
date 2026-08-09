<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_password_from_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertRedirect(route('profile'));
        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'salah-password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors(['current_password']);
        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_without_confirmation_match(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'beda-password',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors(['password']);
        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }
}
