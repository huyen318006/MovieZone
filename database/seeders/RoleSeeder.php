<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::query()->delete();

        Role::insert([
          
            
            [
                'name' => 'ADMIN',
                'description' => 'Administrator with management permissions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'STAFF',
                'description' => 'Staff member with limited permissions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'CUSTOMER',
                'description' => 'Customer role for regular users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
