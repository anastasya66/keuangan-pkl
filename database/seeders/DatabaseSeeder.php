<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial admin, staff, and inactive users.
     */
    public function run(): void
    {
        // Admin Account (Development)
        User::updateOrCreate(
            ['email' => 'admin@fintoko.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make(env('DEV_ADMIN_PASSWORD', 'password')),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Active Staff Account
        User::updateOrCreate(
            ['email' => 'staff@fintoko.test'],
            [
                'name' => 'Staff Toko',
                'password' => Hash::make(env('DEV_STAFF_PASSWORD', 'password')),
                'role' => 'staff',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Inactive Staff Account (For testing inactive account authentication rejection)
        User::updateOrCreate(
            ['email' => 'nonaktif@fintoko.test'],
            [
                'name' => 'Staff Nonaktif',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_active' => false,
                'email_verified_at' => now(),
            ]
        );
    }
}
