<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Owner;
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
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);

<<<<<<< Updated upstream
        // Create a default Owner account
        Owner::create([
            'email' => 'owner@test.com',
            'password' => Hash::make('secret123'),
=======
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
>>>>>>> Stashed changes
        ]);
    }
}
