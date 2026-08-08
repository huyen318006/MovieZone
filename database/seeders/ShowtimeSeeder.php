<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Database\Seeder;

class ShowtimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy danh sách ID thực tế đang CÓ SẴN trong DB
        $movieIds  = Movie::pluck('id')->toArray();
        $cinemaIds = Cinema::pluck('id')->toArray();
        $roomIds   = Room::pluck('id')->toArray();

        // Nếu DB chưa có dữ liệu ở các bảng này thì dừng để không báo lỗi
        if (empty($movieIds) || empty($cinemaIds) || empty($roomIds)) {
            $this->command->error('Thiếu dữ liệu trong bảng movies, cinemas hoặc rooms!');
            return;
        }

        Showtime::query()->delete();

        $showtimes = [];

        for ($i = 1; $i <= 20; $i++) {
            $start = now()->addDays(rand(1, 10));

            $showtimes[] = [
                // Sửa thành bốc ngẫu nhiên ID hợp lệ từ mảng:
                'movie_id'   => $movieIds[array_rand($movieIds)],
                'cinema_id'  => $cinemaIds[array_rand($cinemaIds)],
                'room_id'    => $roomIds[array_rand($roomIds)],
                'start_time' => $start->toDateTimeString(),
                'end_time'   => $start->copy()->addHours(2)->toDateTimeString(),
                'status'     => 'OPEN',
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        Showtime::insert($showtimes);
        $this->command->info('Đã tạo thành công 20 suất chiếu!');
    }
}