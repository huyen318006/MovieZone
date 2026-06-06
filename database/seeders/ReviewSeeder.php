<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Review::query()->delete();

        for ($i = 1; $i <= 20; $i++) {
            Review::create([
                'user_id' => rand(1, 4),
                'movie_id' => rand(1, 5),
                'rating' => rand(3, 5),
                'comment' => 'Great movie!',
                'status' => 'APPROVED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
