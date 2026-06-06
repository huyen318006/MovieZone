<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Movie::query()->delete();

        Movie::insert([
            [
                'title' => 'Avengers Endgame',
                'slug' => 'avengers-endgame',
                'original_title' => 'Avengers: Endgame',
                'description' => 'The Avengers assemble once more to reverse the damage caused by Thanos.',
                'duration_minutes' => 181,
                'release_date' => '2019-04-26',
                'end_date' => '2026-12-31',
                'age_rating' => 'T13',
                'language' => 'English',
                'subtitle' => 'Vietnamese',
                'country' => 'USA',
                'director' => 'Anthony Russo, Joe Russo',
                'cast' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo',
                'poster_url' => 'movies/avengers-endgame-poster.jpg',
                'banner_url' => 'movies/avengers-endgame-banner.jpg',
                'trailer_url' => 'https://youtu.be/TcMBFSGVi1c',
                'status' => 'NOW_SHOWING',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'title' => 'Spider Man No Way Home',
                'slug' => 'spider-man-no-way-home',
                'original_title' => 'Spider-Man: No Way Home',
                'description' => 'Peter Parker seeks help from Doctor Strange.',
                'duration_minutes' => 148,
                'release_date' => '2021-12-17',
                'end_date' => '2026-12-31',
                'age_rating' => 'T13',
                'language' => 'English',
                'subtitle' => 'Vietnamese',
                'country' => 'USA',
                'director' => 'Jon Watts',
                'cast' => 'Tom Holland, Zendaya, Benedict Cumberbatch',
                'poster_url' => 'movies/spider-man-no-way-home-poster.jpg',
                'banner_url' => 'movies/spider-man-no-way-home-banner.jpg',
                'trailer_url' => 'https://youtu.be/JfVOs4VSpmA',
                'status' => 'NOW_SHOWING',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'title' => 'Doctor Strange',
                'slug' => 'doctor-strange',
                'original_title' => 'Doctor Strange',
                'description' => 'Marvel superhero movie.',
                'duration_minutes' => 115,
                'release_date' => '2016-11-04',
                'end_date' => '2026-12-31',
                'age_rating' => 'T13',
                'language' => 'English',
                'subtitle' => 'Vietnamese',
                'country' => 'USA',
                'director' => 'Scott Derrickson',
                'cast' => 'Benedict Cumberbatch, Chiwetel Ejiofor, Rachel McAdams',
                'poster_url' => 'movies/doctor-strange-poster.jpg',
                'banner_url' => 'movies/doctor-strange-banner.jpg',
                'trailer_url' => 'https://youtu.be/HSzx-zryEgM',
                'status' => 'NOW_SHOWING',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'title' => 'Doraemon Movie',
                'slug' => 'doraemon-movie',
                'original_title' => 'Doraemon',
                'description' => 'Popular Japanese animation movie.',
                'duration_minutes' => 110,
                'release_date' => '2024-03-15',
                'end_date' => '2026-12-31',
                'age_rating' => 'P',
                'language' => 'Japanese',
                'subtitle' => 'Vietnamese',
                'country' => 'Japan',
                'director' => 'Takumi Doyama',
                'cast' => 'Doraemon, Nobita, Shizuka',
                'poster_url' => 'movies/doraemon-poster.jpg',
                'banner_url' => 'movies/doraemon-banner.jpg',
                'trailer_url' => 'https://youtu.be/voYtI2lWOss',
                'status' => 'COMING_SOON',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'title' => 'Your Name',
                'slug' => 'your-name',
                'original_title' => 'Kimi no Na wa',
                'description' => 'Romantic fantasy anime film.',
                'duration_minutes' => 106,
                'release_date' => '2016-08-26',
                'end_date' => '2026-12-31',
                'age_rating' => 'P',
                'language' => 'Japanese',
                'subtitle' => 'Vietnamese',
                'country' => 'Japan',
                'director' => 'Makoto Shinkai',
                'cast' => 'Ryunosuke Kamiki, Mone Kamishiraishi',
                'poster_url' => 'movies/your-name-poster.jpg',
                'banner_url' => 'movies/your-name-banner.jpg',
                'trailer_url' => 'https://youtu.be/xU47nhruN-Q',
                'status' => 'NOW_SHOWING',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
