<?php

namespace Database\Seeders;

use App\Models\BookingCombo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingComboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BookingCombo::query()->delete();

        for ($i = 1; $i <= 20; $i++) {
            $unitPrice = rand(99000, 199000);
            $quantity = rand(1, 3);

            BookingCombo::create([
                'booking_id' => rand(1, 20),
                'combo_id' => rand(1, 2),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
