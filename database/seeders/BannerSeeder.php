<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Banner::query()->delete();

        Banner::insert([
            [
                'title' => 'Summer Promotion',
                'image_url' => 'banners/banner1.jpg',
                'link_url' => 'https://moviezone.example.com/summer',
                'position' => 'TOP',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Weekend Sale',
                'image_url' => 'banners/banner2.jpg',
                'link_url' => 'https://moviezone.example.com/weekend',
                'position' => 'BOTTOM',
                'start_date' => now()->addDays(1),
                'end_date' => now()->addWeeks(2),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
