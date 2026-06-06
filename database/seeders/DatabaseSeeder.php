<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();


        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserRoleSeeder::class,
            RolePermissionSeeder::class,

            GenreSeeder::class,
            MovieSeeder::class,
            MovieGenreSeeder::class,

            CinemaSeeder::class,
            RoomSeeder::class,
            SeatSeeder::class,

            TicketPriceSeeder::class,
            ShowtimeSeeder::class,
            ShowtimeSeatSeeder::class,

            ProductSeeder::class,
            ComboSeeder::class,
            ComboItemSeeder::class,

            PromotionSeeder::class,
            VoucherSeeder::class,

            MembershipLevelSeeder::class,
            UserMembershipSeeder::class,

            BookingSeeder::class,
            BookingSeatSeeder::class,
            BookingComboSeeder::class,

            TicketSeeder::class,
            PaymentSeeder::class,

            VoucherUsageSeeder::class,
            PointTransactionSeeder::class,

            ArticleSeeder::class,
            BannerSeeder::class,
            ReviewSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
