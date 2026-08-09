<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate([
            'email' => 'admin@atv.kominfo',
        ], [
            'name' => 'Administrator ATV',
            'password' => 'atv12345',
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        User::updateOrCreate([
            'email' => 'staff@atv.kominfo',
        ], [
            'name' => 'Staff ATV',
            'password' => 'staff12345',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }
}
