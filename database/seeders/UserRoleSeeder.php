<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\UserRole;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserRole::query()->delete();

        $userRoles = [
            ['user_id' => 1, 'role_id' => 1, 'assigned_at' => now()], // ADMIN
            ['user_id' => 2, 'role_id' => 1, 'assigned_at' => now()], // ADMIN
            ['user_id' => 3, 'role_id' => 2, 'assigned_at' => now()], // STAFF
            ['user_id' => 4, 'role_id' => 1, 'assigned_at' => now()], // CUSTOMER
        ];

        UserRole::insert($userRoles);
    }
}
