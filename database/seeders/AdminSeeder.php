<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@greengrasstravels.com',
            'password' => Hash::make('SuperAdmin@2024'),
            'role' => 'super_admin',
            'phone' => '08012345678',
            'is_active' => true,
        ]);

        // Create regular admin
        Admin::create([
            'name' => 'Regular Admin',
            'email' => 'staff@greengrasstravels.com',
            'password' => Hash::make('Staff@2024'),
            'role' => 'admin',
            'phone' => '08087654321',
            'is_active' => true,
        ]);

        // Create manager
        Admin::create([
            'name' => 'Manager',
            'email' => 'manager@blueleaftravels.com',
            'password' => Hash::make('Manager@2024'),
            'role' => 'manager',
            'phone' => '08011223344',
            'is_active' => true,
        ]);
    }
}