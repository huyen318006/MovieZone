<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'code' => 'WELCOME50',
                'discount_type' => 'FIXED',
                'discount_value' => 50000,
                'max_discount' => 100000,
                'min_order_amount' => 200000,
                'usage_limit' => 100,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MOVIE20',
                'discount_type' => 'PERCENT',
                'discount_value' => 20,
                'max_discount' => 50000,
                'min_order_amount' => 100000,
                'usage_limit' => 200,
                'usage_per_user' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(2),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
