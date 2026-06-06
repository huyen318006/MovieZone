<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Promotion::query()->delete();

        Promotion::insert([
            [
                'title' => 'Summer Sale',
                'description' => 'Save on tickets this summer',
                'banner_url' => 'promotions/summer-sale.jpg',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Weekend Sale',
                'description' => 'Discounts every weekend',
                'banner_url' => 'promotions/weekend-sale.jpg',
                'start_date' => now()->addDays(1),
                'end_date' => now()->addWeeks(2),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
