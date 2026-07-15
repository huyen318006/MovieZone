<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Genre::query()->delete();

        $genres = [
            'Hành động',
            'Phiêu lưu',
            'Hài',
            'Chính kịch',
            'Giả tưởng',
            'Kinh dị',
            'Lãng mạn',
            'Khoa học viễn tưởng',
            'Giật gân',
            'Hoạt hình'
        ];

        foreach ($genres as $genre) {
            Genre::create([
                'name' => $genre,
                'slug' => Str::slug($genre),
                'description' => 'Popular ' . $genre . ' movies',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
