<?php

namespace Database\Seeders;

use App\Models\VoucherUsage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VoucherUsageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VoucherUsage::query()->delete();

        for ($i = 1; $i <= 10; $i++) {
            VoucherUsage::create([
                'voucher_id' => rand(1, 2),
                'user_id' => rand(1, 4),
                'booking_id' => rand(1, 20),
                'used_at' => now()->subDays(rand(1, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
