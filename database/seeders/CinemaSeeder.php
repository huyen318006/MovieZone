<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CinemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $now = Carbon::now();

        DB::table('cinemas')->insert([
            [
                'name' => 'MOVIEZONE',
                'city' => 'Hà Nội',
                'district' => 'Hai Bà Trưng',
                'address' => '191 Bà Triệu, Hai Bà Trưng, Hà Nội',
                'hotline' => '19006017',
                'map_url' => 'https://maps.google.com/?q=CGV+Vincom+Ba+Trieu',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
