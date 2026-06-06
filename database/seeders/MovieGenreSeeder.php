<?php

namespace Database\Seeders;

use App\Models\MovieGenre;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MovieGenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        MovieGenre::truncate();

        MovieGenre::insert([
            ['movie_id' => 1, 'genre_id' => 1],
            ['movie_id' => 1, 'genre_id' => 2],
            ['movie_id' => 2, 'genre_id' => 1],
            ['movie_id' => 3, 'genre_id' => 8],
            ['movie_id' => 4, 'genre_id' => 10],
            ['movie_id' => 5, 'genre_id' => 7],
        ]);
    }
}
