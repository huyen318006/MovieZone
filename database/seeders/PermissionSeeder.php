<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::query()->delete();

        Permission::insert([
            [
                'code' => 'USER_VIEW',
                'name' => 'user.view',
                'description' => 'View users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USER_CREATE',
                'name' => 'user.create',
                'description' => 'Create users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USER_UPDATE',
                'name' => 'user.update',
                'description' => 'Update users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USER_DELETE',
                'name' => 'user.delete',
                'description' => 'Delete users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MOVIE_VIEW',
                'name' => 'movie.view',
                'description' => 'View movies',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MOVIE_CREATE',
                'name' => 'movie.create',
                'description' => 'Create movies',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MOVIE_UPDATE',
                'name' => 'movie.update',
                'description' => 'Update movies',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MOVIE_DELETE',
                'name' => 'movie.delete',
                'description' => 'Delete movies',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BOOKING_MANAGE',
                'name' => 'booking.manage',
                'description' => 'Manage bookings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PAYMENT_MANAGE',
                'name' => 'payment.manage',
                'description' => 'Manage payments',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
