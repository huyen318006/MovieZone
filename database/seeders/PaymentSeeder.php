<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payment::query()->delete();

        for ($i = 1; $i <= 20; $i++) {
            Payment::create([
                'booking_id' => $i,
                'amount' => rand(100000, 500000),
                'payment_method' => 'VNPAY',
                'transaction_code' => 'TRX' . strtoupper(uniqid()),
                'raw_response' => json_encode(['status' => 'SUCCESS']),
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
