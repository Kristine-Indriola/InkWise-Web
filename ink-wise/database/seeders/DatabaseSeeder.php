<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Owner
        $owner = User::create([
            'email' => 'owner@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        // Staff users
        $staff1 = User::create([
            'email' => 'staff1@test.com',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        $staff2 = User::create([
            'email' => 'staff2@test.com',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Staff & Admin details
        DB::table('staff')->insert([
            // Admin profile
            [
                'user_id' => $admin->user_id, // ✅ FK to users table
                'role' => 'admin',
                'first_name' => 'Super',
                'middle_name' => null,
                'last_name' => 'Admin',
                'contact_number' => '0917-000-0000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Staff 1 profile
            [
                'user_id' => $staff1->user_id,
                'role' => 'staff',
                'first_name' => 'John',
                'middle_name' => 'M.',
                'last_name' => 'Doe',
                'contact_number' => '123-456-7890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Staff 2 profile
            [
                'user_id' => $staff2->user_id,
                'role' => 'staff',
                'first_name' => 'Jane',
                'middle_name' => null,
                'last_name' => 'Smith',
                'contact_number' => '123-456-7890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
