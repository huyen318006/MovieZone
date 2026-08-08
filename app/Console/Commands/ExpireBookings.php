<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\PointTransaction;
use App\Services\CoinRedemptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireBookings extends Command
{
    protected $signature = 'booking:expire-pending';

    protected $description = 'Tự động chuyển booking quá hạn (expired_at < now) sang trạng thái EXPIRED và giải phóng ghế';

    public function handle(): int
    {
        $expiredBookings = Booking::whereIn('status', ['PENDING', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now())
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('✅ Không có booking nào cần expire.');
            return self::SUCCESS;
        }

        $this->info("📋 Tìm thấy {$expiredBookings->count()} booking quá hạn.");

        $expiredCount = 0;

        foreach ($expiredBookings as $booking) {
            try {
                DB::transaction(function () use ($booking) {
                    // Chuyển trạng thái booking
                    $booking->update([
                        'status' => 'EXPIRED',
                        'payment_status' => $booking->payment_status === 'PAID' ? 'PAID' : 'FAILED',
                    ]);

                    // Hoàn xu nếu đã trừ
                    $redeemTx = PointTransaction::where('booking_id', $booking->id)
                        ->where('type', 'REDEEM')
                        ->first();

                    if ($redeemTx && $booking->user_id) {
                        app(CoinRedemptionService::class)->refundCoins(
                            $booking->user_id,
                            abs($redeemTx->points),
                            $booking->id
                        );
                    }

                    // Giải phóng cache giữ ghế
                    $bookingSeats = DB::table('booking_seats')
                        ->where('booking_id', $booking->id)
                        ->pluck('showtime_seat_id');

                    foreach ($bookingSeats as $showtimeSeatId) {
                        $cacheKey = 'seat_held_' . $booking->showtime_id . '_' . $showtimeSeatId;
                        Cache::forget($cacheKey);
                    }

                    // Giải phóng master timer
                    if ($booking->user_id) {
                        $masterTimerKey = 'hold_timer_' . $booking->user_id . '_' . $booking->showtime_id;
                        Cache::forget($masterTimerKey);
                    }

                    // Expire SepayOrder tương ứng
                    DB::table('sepay_orders')
                        ->where('booking_id', $booking->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'expired']);
                });

                $expiredCount++;

                Log::info('Booking expired by scheduler', [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'expired_at' => $booking->expired_at,
                ]);

            } catch (\Exception $e) {
                Log::error('Lỗi khi expire booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("❌ Lỗi expire booking #{$booking->id}: {$e->getMessage()}");
            }
        }

        $this->info("✅ Đã expire {$expiredCount}/{$expiredBookings->count()} booking.");

        return self::SUCCESS;
    }
}
