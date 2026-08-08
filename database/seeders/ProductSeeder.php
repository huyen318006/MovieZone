<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::query()->delete();

        $products = [
            ['name' => 'Bắp rang nhỏ', 'description' => 'Hộp bắp rang nhỏ, vị butter', 'image_url' => 'products/SP-Bắp rang nhỏ.png', 'price' => 45000, 'status' => 'ACTIVE'],
            ['name' => 'Bắp rang vừa', 'description' => 'Hộp bắp rang vừa, vị caramel', 'image_url' => 'products/SP-Bắp rang vừa.jpg', 'price' => 65000, 'status' => 'ACTIVE'],
            ['name' => 'Bắp rang lớn', 'description' => 'Hộp bắp rang lớn, vị cheese', 'image_url' => 'products/SP-Bắp rang lớn.png', 'price' => 85000, 'status' => 'ACTIVE'],
            ['name' => 'Coca Cola 320ml', 'description' => 'Nước ngọt cola lạnh', 'image_url' => 'products/SP-Nc Coca Cola 320ml.jpg', 'price' => 30000, 'status' => 'ACTIVE'],
            ['name' => 'Pepsi 320ml', 'description' => 'Nước ngọt pepsi lạnh', 'image_url' => 'products/SP-Nc Pepsi 320ml.jpg', 'price' => 30000, 'status' => 'ACTIVE'],
            ['name' => 'Sprite 320ml', 'description' => 'Nước ngọt sprite lạnh', 'image_url' => 'products/SP-Nc Sprite 320ml.jpg', 'price' => 30000, 'status' => 'ACTIVE'],
            ['name' => 'Nước khoáng Lavie', 'description' => 'Nước khoáng 500ml', 'image_url' => 'products/SP-Nc khoáng Lavie.jpg', 'price' => 20000, 'status' => 'ACTIVE'],
            ['name' => 'Khoai tây chiên', 'description' => 'Món khai vị thơm ngon', 'image_url' => 'products/SP-Khoai tây chiên.jpg', 'price' => 40000, 'status' => 'ACTIVE'],
            ['name' => 'Nachos phô mai', 'description' => 'Nachos nóng với phô mai', 'image_url' => 'products/SP-Nachos phô mai.jpg', 'price' => 45000, 'status' => 'ACTIVE'],
            ['name' => 'Hotdog', 'description' => 'Hotdog nóng giòn', 'image_url' => 'products/SP-Hotdog.jpg', 'price' => 50000, 'status' => 'ACTIVE'],
        ];

        foreach ($products as $product) {
            Product::create([
                'name' => $product['name'],
                'description' => $product['description'],
                'image_url' => $product['image_url'],
                'price' => $product['price'],
                'status' => $product['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
