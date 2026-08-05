<?php

namespace Database\Seeders;

use App\Models\MovieGenre;
use Illuminate\Database\Seeder;

class MovieGenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MovieGenre::query()->delete();

        $mappings = [
            [1, 1], [1, 2],
            [2, 1], [2, 10],
            [3, 1], [3, 8],
            [4, 1], [4, 8],
            [5, 1], [5, 2],
            [6, 10], [6, 2],
            [7, 1], [7, 2],
            [8, 6], [8, 1],
            [9, 1], [9, 5],
            [10, 7], [10, 2],
            [11, 5], [11, 1],
            [12, 7], [12, 10],
            [13, 8], [13, 2],
            [14, 1], [14, 2],
            [15, 4], [15, 6],
            [16, 1], [16, 9],
            [17, 7], [17, 10],
            [18, 1], [18, 2],
            [19, 10], [19, 2],
            [20, 1], [20, 2],
        ];

        foreach ($mappings as [$movieId, $genreId]) {
            MovieGenre::create([
                'movie_id' => $movieId,
                'genre_id' => $genreId,
            ]);
        }
    }
}
