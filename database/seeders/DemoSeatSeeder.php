<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seat;
use App\Models\Room;

class DemoSeatSeeder extends Seeder
{
    /**
     * Tạo ghế demo với giá 10,000 VND.
     */
    public function run(): void
    {
        // Lấy phòng đầu tiên có sẵn
        $room = Room::first();

        if (!$room) {
            $this->command->error('Không tìm thấy phòng chiếu nào! Hãy tạo phòng trước.');
            return;
        }

        $seat = Seat::create([
            'room_id'     => $room->id,
            'row_label'   => 'Z',
            'seat_number' => 99,
            'seat_code'   => 'Z99',
            'seat_type'   => 'STANDARD',
            'status'      => 'ACTIVE',
            'price'       => 10000,
        ]);

        $this->command->info("✅ Đã tạo ghế demo thành công!");
        $this->command->info("   - ID: {$seat->id}");
        $this->command->info("   - Mã ghế: {$seat->seat_code}");
        $this->command->info("   - Phòng: {$room->name} (ID: {$room->id})");
        $this->command->info("   - Loại: {$seat->seat_type}");
        $this->command->info("   - Giá: 10,000 VND");
    }
}
