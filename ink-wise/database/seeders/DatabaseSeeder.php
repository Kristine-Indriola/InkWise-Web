<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default Admin user
        User::create([
            'first_name' => 'System',
            'middle_name' => null,
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create a default Owner account
        User::create([
            'first_name' => 'Default',
            'middle_name' => null,
            'last_name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'owner',
            'status' => 'active',
        ]);
    }
}
