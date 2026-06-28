<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->delete();

        User::insert([
            [
                'name'              => 'Admin',
                'phone'             => '0123456789',
                'email'             => 'admin@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'           =>'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'Admin',
                'phone'             => '0987654321',
                'email'             => 'admin@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'           => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'Staff',
                'phone'             => '0112233445',
                'email'             => 'customer@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'           => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'Admin',
                'phone'             => '0223344556',
                'email'             => 'nguyenhungpro2k6@gmail.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'           => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }
}
