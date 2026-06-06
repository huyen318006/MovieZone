<?php

namespace Database\Seeders;

use App\Models\Cinema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CinemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Cinema::query()->delete();

        Cinema::insert([
            [
                'name' => 'MovieZone Hà Nội',
                'city' => 'Hà Nội',
                'district' => 'Hoàn Kiếm',
                'address' => '123 Tràng Tiền, Hoàn Kiếm, Hà Nội',
                'hotline' => '19001001',
                'map_url' => 'https://maps.google.com',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'MovieZone Đà Nẵng',
                'city' => 'Đà Nẵng',
                'district' => 'Hải Châu',
                'address' => '456 Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
                'hotline' => '19001002',
                'map_url' => 'https://maps.google.com',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'MovieZone Hồ Chí Minh',
                'city' => 'Hồ Chí Minh',
                'district' => 'Quận 1',
                'address' => '789 Lê Lợi, Quận 1, TP.HCM',
                'hotline' => '19001003',
                'map_url' => 'https://maps.google.com',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
