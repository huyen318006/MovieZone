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
                'image_url' => 'banners/banner1.jpg',
                'link_url' => 'http://127.0.0.1:8000/showtimes',
                'position' => 'HOME_TOP',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'image_url' => 'banners/banner2.jpg',
                'link_url' => 'http://127.0.0.1:8000/news',
                'position' => 'HOME_MIDDLE',
                'start_date' => now()->addDays(1),
                'end_date' => now()->addWeeks(2),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'image_url' => 'banners/banner3.jpg',
                'link_url' => 'http://127.0.0.1:8000/movies',
                'position' => 'HOME_MIDDLE',
                'start_date' => now()->addDays(1),
                'end_date' => now()->addWeeks(2),
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
