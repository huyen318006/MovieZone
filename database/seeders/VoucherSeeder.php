<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Voucher::query()->delete();

        Voucher::insert([
            [
                'code' => 'BRONZE10',
                'discount_type' => 'FIXED',
                'discount_value' => 10000,
                'max_discount' => 10000,
                'min_order_amount' => 50000,
                'usage_limit' => 500,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SILVER20K',
                'discount_type' => 'FIXED',
                'discount_value' => 20000,
                'max_discount' => 20000,
                'min_order_amount' => 100000,
                'usage_limit' => 300,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GOLD50K',
                'discount_type' => 'FIXED',
                'discount_value' => 50000,
                'max_discount' => 50000,
                'min_order_amount' => 150000,
                'usage_limit' => 200,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PLATINUM80K',
                'discount_type' => 'FIXED',
                'discount_value' => 80000,
                'max_discount' => 80000,
                'min_order_amount' => 200000,
                'usage_limit' => 100,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DIAMOND100K',
                'discount_type' => 'FIXED',
                'discount_value' => 100000,
                'max_discount' => 100000,
                'min_order_amount' => 250000,
                'usage_limit' => 50,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}