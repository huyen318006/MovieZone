<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AuditLog::query()->delete();

        $logs = [];
        for ($i = 1; $i <= 30; $i++) {
            $logs[] = [
                'user_id'     => rand(1, 4),
                'action'      => 'CREATE',
                'entity_name' => 'Booking',
                'entity_id'   => (string) rand(1, 20),
                'old_value'   => null,
                'new_value'   => null,
                'created_at'  => now()->subMinutes(rand(1, 120)),
            ];
        }
        AuditLog::insert($logs);
    }
}
