<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::query()->delete();

        Product::insert([
            [
                'name' => 'Popcorn Small',
                'description' => 'Small popcorn',
                'image_url' => 'products/popcorn-small.jpg',
                'price' => 45000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Popcorn Large',
                'description' => 'Large popcorn',
                'image_url' => 'products/popcorn-large.jpg',
                'price' => 65000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Coca Cola',
                'description' => 'Cold drink',
                'image_url' => 'products/coca-cola.jpg',
                'price' => 30000,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
