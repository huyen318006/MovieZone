<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;

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
            ['user_id' => 3, 'role_id' => 3, 'assigned_at' => now()], // CUSTOMER (customer@moviezone.com)
            ['user_id' => 4, 'role_id' => 3, 'assigned_at' => now()], // CUSTOMER (nguyenhungpro2k6@gmail.com)
            ['user_id' => 5, 'role_id' => 3, 'assigned_at' => now()], // CUSTOMER (account5@moviezone.com)
            ['user_id' => 6, 'role_id' => 3, 'assigned_at' => now()], // CUSTOMER (account6@moviezone.com)
            ['user_id' => 7, 'role_id' => 3, 'assigned_at' => now()], // CUSTOMER (account7@moviezone.com)
        ];

        UserRole::insert($userRoles);
    }
}
