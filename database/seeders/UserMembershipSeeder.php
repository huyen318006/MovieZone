<?php

namespace Database\Seeders;

use App\Models\UserMembership;
use Illuminate\Database\Seeder;

class UserMembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserMembership::query()->delete();

        UserMembership::insert([
            [
                'user_id'          => 1,
                'level_id'         => 3,
                'points'           => 8000,
                'total_spent'      => 2000000,
                'level_expired_at' => now()->addMonths(6),
                'updated_at'       => now(),
            ],
            [
                'user_id'          => 2,
                'level_id'         => 3,
                'points'           => 2500,
                'total_spent'      => 2000000,
                'level_expired_at' => now()->addMonths(6),
                'updated_at'       => now(),
            ],
            [
                'user_id'          => 3,
                'level_id'         => 1,
                'points'           => 200,
                'total_spent'      => 10000,
                'level_expired_at' => now()->addMonths(6),
                'updated_at'       => now(),
            ],
            [
                'user_id'          => 4,
                'level_id'         => 3,
                'points'           => 5000,
                'total_spent'      => 2000000,
                'level_expired_at' => now()->addMonths(6),
                'updated_at'       => now(),
            ],
            // 🔥 TÀI KHOẢN MAU DEMO 1: Hạng DIAMOND quá hạn 7 tháng (Không mua vé -> Sẽ bị hạ về PLATINUM khi quét)
            [
                'user_id'          => 5,
                'level_id'         => 5, // DIAMOND
                'points'           => 15000,
                'total_spent'      => 12000000,
                'level_expired_at' => now()->subMonths(7),
                'updated_at'       => now()->subMonths(7),
            ],
            // 🔥 TÀI KHOẢN MẪU DEMO 2: Hạng GOLD quá hạn 8 tháng (Không mua vé -> Sẽ bị hạ về SILVER khi quét)
            [
                'user_id'          => 6,
                'level_id'         => 3, // GOLD
                'points'           => 4000,
                'total_spent'      => 3500000,
                'level_expired_at' => now()->subMonths(8),
                'updated_at'       => now()->subMonths(8),
            ],
            // 🔥 TÀI KHOẢN MẪU DEMO 3: Hạng GOLD vừa hết hạn 5 ngày (CÓ MUA VÉ TRONG 6T -> Sẽ được GIA HẠN THÊM 6 THÁNG)
            [
                'user_id'          => 7,
                'level_id'         => 3, // GOLD
                'points'           => 3000,
                'total_spent'      => 2500000,
                'level_expired_at' => now()->subDays(5),
                'updated_at'       => now()->subDays(5),
            ],
        ]);
    }
}
