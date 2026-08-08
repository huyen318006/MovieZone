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
                'id'                => 1,
                'name'              => 'Admin 1',
                'phone'             => '0123456789',
                'email'             => 'admin2@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 2,
                'name'              => 'Admin 2',
                'phone'             => '0987654321',
                'email'             => 'admin1@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 3,
                'name'              => 'Customer Test',
                'phone'             => '0112233445',
                'email'             => 'customer@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 4,
                'name'              => 'Phan Nguyên Hùng',
                'phone'             => '0223344556',
                'email'             => 'nguyenhungpro2k6@gmail.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 5,
                'name'              => 'Trần Văn A',
                'phone'             => '0901112233',
                'email'             => 'account5@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now()->subMonths(12),
                'updated_at'        => now(),
            ],
            [
                'id'                => 6,
                'name'              => 'Lê Thị B',
                'phone'             => '0904445566',
                'email'             => 'account6@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now()->subMonths(10),
                'updated_at'        => now(),
            ],
            [
                'id'                => 7,
                'name'              => 'Nguyễn Văn C',
                'phone'             => '0907778899',
                'email'             => 'account7@moviezone.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'status'            => 'ACTIVE',
                'created_at'        => now()->subMonths(8),
                'updated_at'        => now(),
            ],
        ]);
    }
}
