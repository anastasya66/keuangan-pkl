<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial admin and staff users.
     */
    public function run(): void
    {
        // Admin Account
        User::updateOrCreate(
            ['email' => 'admin@fintoko.test'],
            [
                'name' => 'Administrator FinToko',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Staff Account
        User::updateOrCreate(
            ['email' => 'staff@fintoko.test'],
            [
                'name' => 'Staff Toko',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ]
        );
    }
}
