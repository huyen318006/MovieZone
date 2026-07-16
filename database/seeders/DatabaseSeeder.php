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
    // Master data
    RoleSeeder::class,
    PermissionSeeder::class,
    GenreSeeder::class,
    TicketPriceSeeder::class,
    ProductSeeder::class,
    PromotionSeeder::class,
    MembershipLevelSeeder::class,
    BannerSeeder::class,

    // Users
    UserSeeder::class,

    // RBAC
    UserRoleSeeder::class,
    RolePermissionSeeder::class,

    // Movies
    MovieSeeder::class,
    MovieGenreSeeder::class,

    // Cinema structure
    CinemaSeeder::class,
    RoomSeeder::class,
    SeatSeeder::class,

    // Showtimes
    ShowtimeSeeder::class,
    ShowtimeSeatSeeder::class,

    // Combo
    ComboSeeder::class,
    ComboItemSeeder::class,

    // Membership & Voucher
    VoucherSeeder::class,
    UserMembershipSeeder::class,

    // Booking
    BookingSeeder::class,
    BookingSeatSeeder::class,
    BookingComboSeeder::class,
    BookingCancellationSeeder::class,

    // Payment
    TicketSeeder::class,
    PaymentSeeder::class,

    // Logs
    VoucherUsageSeeder::class,
    PointTransactionSeeder::class,
    ReviewSeeder::class,
    ArticleSeeder::class,
    AuditLogSeeder::class,

    // Demo testing
    DemoBookingSeeder::class,
]);
    }
}
