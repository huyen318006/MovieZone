<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Article::query()->delete();

        for ($i = 1; $i <= 10; $i++) {
            Article::create([
                'title' => 'Movie News ' . $i,
                'slug' => Str::slug('Movie News ' . $i),
                'summary' => 'Summary of Movie News ' . $i,
                'content' => 'Sample article content ' . $i,
                'thumbnail_url' => 'articles/thumb' . $i . '.jpg',
                'category' => 'Entertainment',
                'status' => 'PUBLISHED',
                'published_at' => now()->subDays(10 - $i),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
