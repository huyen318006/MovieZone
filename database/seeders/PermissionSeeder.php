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

            // ── UC-STAFF-03: Tra cứu Booking/Vé ──
            [
                'code' => 'BOOKING_LOOKUP',
                'name' => 'booking.lookup',
                'description' => 'Tra cứu và xem thông tin booking/vé',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BOOKING_LOOKUP_ALL_CINEMAS',
                'name' => 'booking.lookup.all_cinemas',
                'description' => 'Tra cứu booking ở tất cả rạp (không giới hạn)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BOOKING_VIEW_FULL_CUSTOMER',
                'name' => 'booking.view_full_customer',
                'description' => 'Xem đầy đủ thông tin KH không bị mask',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'AUDIT_LOG_VIEW',
                'name' => 'audit_log.view',
                'description' => 'Xem audit log của booking',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
