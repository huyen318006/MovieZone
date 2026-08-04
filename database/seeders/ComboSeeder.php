<?php

namespace Database\Seeders;

use App\Models\Combo;
use Illuminate\Database\Seeder;

class ComboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Combo::query()->delete();

        $combos = [
            ['name' => 'Combo Cặp đôi', 'description' => '1 bắp + 2 nước + 2 khoai tây', 'price' => 109000, 'image_url' => 'uploads/combos/combo-couple.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Gia đình', 'description' => '2 bắp + 4 nước + 2 khoai tây', 'price' => 199000, 'image_url' => 'uploads/combos/combo-family.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Movie Night', 'description' => '2 bắp + 2 nước + 1 hotdog', 'price' => 139000, 'image_url' => 'uploads/combos/combo-movie-night.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Tiệc nhỏ', 'description' => '1 bắp lớn + 2 nước + 1 nachos', 'price' => 129000, 'image_url' => 'uploads/combos/combo-small-party.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Hướng dẫn viên', 'description' => '2 bắp vừa + 2 nước + 1 khoai tây', 'price' => 119000, 'image_url' => 'uploads/combos/combo-guide.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Sinh nhật', 'description' => '2 bắp + 2 nước + 2 hotdog', 'price' => 149000, 'image_url' => 'uploads/combos/combo-birthday.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Team Building', 'description' => '3 bắp + 3 nước + 3 khoai tây', 'price' => 229000, 'image_url' => 'uploads/combos/combo-team-building.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Kết nối', 'description' => '1 bắp lớn + 2 nước + 1 nachos', 'price' => 135000, 'image_url' => 'uploads/combos/combo-connect.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo Học sinh', 'description' => '1 bắp nhỏ + 1 nước + 1 khoai tây', 'price' => 89000, 'image_url' => 'uploads/combos/combo-student.svg', 'status' => 'ACTIVE'],
            ['name' => 'Combo VIP', 'description' => '2 bắp lớn + 2 nước + 2 hotdog', 'price' => 249000, 'image_url' => 'uploads/combos/combo-vip.svg', 'status' => 'ACTIVE'],
        ];

        foreach ($combos as $combo) {
            Combo::create([
                'name' => $combo['name'],
                'description' => $combo['description'],
                'price' => $combo['price'],
                'image_url' => $combo['image_url'],
                'status' => $combo['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
