<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --------------------
        // Admin
        // --------------------
        $admin = User::create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        // --------------------
        // Owner
        // --------------------
        $owner = User::create([
            'email'    => 'owner@test.com',
            'password' => Hash::make('secret123'),
            'role'     => 'owner',
            'status'   => 'active',
        ]);

        // --------------------
        // Staff accounts (pending)
        // --------------------
        $staff1 = User::create([
            'email'    => 'staff1@test.com',
            'password' => Hash::make('staff123'),
            'role'     => 'staff',
            'status'   => 'inactive',
        ]);

        $staff2 = User::create([
            'email'    => 'staff2@test.com',
            'password' => Hash::make('staff123'),
            'role'     => 'staff',
            'status'   => 'inactive',
        ]);

        // --------------------
        // Staff & Admin details (using Eloquent so staff_id is auto-generated)
        // --------------------
        Staff::create([
            'user_id'        => $admin->user_id,
            'role'           => 'admin',
            'first_name'     => 'Super',
            'middle_name'    => null,
            'last_name'      => 'Admin',
            'contact_number' => '0917-000-0000',
            'status'         => 'approved',
        ]);

        Staff::create([
            'user_id'        => $owner->user_id,
            'role'           => 'owner',
            'first_name'     => 'Owner',
            'middle_name'    => null,
            'last_name'      => 'Test',
            'contact_number' => '4565-456-7854',
            'status'         => 'approved',
        ]);

        Staff::create([
            'user_id'        => $staff1->user_id,
            'role'           => 'staff',
            'first_name'     => 'John',
            'middle_name'    => 'M.',
            'last_name'      => 'Doe',
            'contact_number' => '0917-111-1111',
            'status'         => 'pending',
        ]);

        Staff::create([
            'user_id'        => $staff2->user_id,
            'role'           => 'staff',
            'first_name'     => 'Jane',
            'middle_name'    => null,
            'last_name'      => 'Smith',
            'contact_number' => '0917-222-2222',
            'status'         => 'pending',
        ]);

        // --------------------
        // Staff Addresses
        // --------------------
        DB::table('addresses')->insert([
            [
                'user_id'    => $staff1->user_id,
                'street'     => '123 Main St',
                'barangay'   => 'Quezon',
                'city'       => 'Quezon City',
                'province'   => 'Metro Manila',
                'postal_code'=> '1100',
                'country'    => 'Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => $staff2->user_id,
                'street'     => '456 Sampaguita Ave',
                'barangay'   => 'Quezon',
                'city'       => 'Makati',
                'province'   => 'Metro Manila',
                'postal_code'=> '1200',
                'country'    => 'Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [

                'user_id'    => $owner->user_id,
                'street'     => '123 Main St',
                'barangay'   => 'Quezon',
                'city'       => 'Quezon City',
                'province'   => 'Metro Manila',
                'postal_code'=> '1100',
                'country'    => 'Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => $admin->user_id,
                'street'     => '456 Sampaguita Ave',
                'barangay'   => 'Quezon',
                'city'       => 'Makati',
                'province'   => 'Metro Manila',
                'postal_code'=> '1200',
                'country'    => 'Philippines',
                'created_at' => now(),
                'updated_at' => now(),
            ]
            
        ]);
    }
}
