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
            ['name' => 'Bắp rang nhỏ', 'description' => 'Hộp bắp rang nhỏ, vị butter', 'image_url' => 'uploads/products/popcorn-small.svg', 'price' => 45000, 'status' => 'ACTIVE'],
            ['name' => 'Bắp rang vừa', 'description' => 'Hộp bắp rang vừa, vị caramel', 'image_url' => 'uploads/products/popcorn-medium.svg', 'price' => 65000, 'status' => 'ACTIVE'],
            ['name' => 'Bắp rang lớn', 'description' => 'Hộp bắp rang lớn, vị cheese', 'image_url' => 'uploads/products/popcorn-large.svg', 'price' => 85000, 'status' => 'ACTIVE'],
            ['name' => 'Coca Cola 320ml', 'description' => 'Nước ngọt cola lạnh', 'image_url' => 'uploads/products/coca-cola.svg', 'price' => 30000, 'status' => 'ACTIVE'],
            ['name' => 'Pepsi 320ml', 'description' => 'Nước ngọt pepsi lạnh', 'image_url' => 'uploads/products/pepsi.svg', 'price' => 30000, 'status' => 'ACTIVE'],
            ['name' => 'Sprite 320ml', 'description' => 'Nước ngọt sprite lạnh', 'image_url' => 'uploads/products/sprite.svg', 'price' => 30000, 'status' => 'ACTIVE'],
            ['name' => 'Nước khoáng Lavie', 'description' => 'Nước khoáng 500ml', 'image_url' => 'uploads/products/water.svg', 'price' => 20000, 'status' => 'ACTIVE'],
            ['name' => 'Khoai tây chiên', 'description' => 'Món khai vị thơm ngon', 'image_url' => 'uploads/products/french-fries.svg', 'price' => 40000, 'status' => 'ACTIVE'],
            ['name' => 'Nachos phô mai', 'description' => 'Nachos nóng với phô mai', 'image_url' => 'uploads/products/nachos.svg', 'price' => 45000, 'status' => 'ACTIVE'],
            ['name' => 'Hotdog', 'description' => 'Hotdog nóng giòn', 'image_url' => 'uploads/products/hotdog.svg', 'price' => 50000, 'status' => 'ACTIVE'],
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
