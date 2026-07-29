<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coin;
use App\Models\MembershipLevel;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    /**
     * Tự động khởi tạo ví Coin và Membership mặc định cho Customer nếu chưa có.
     */
    public function ensureMembership(User $user): UserMembership
    {
        // 1. Tạo ví Coin số dư = 0 nếu chưa có
        Coin::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // 2. Tìm hạng mặc định (hạng có min_points thấp nhất, thường là BRONZE)
        $defaultLevel = MembershipLevel::orderBy('min_points', 'asc')->first();
        $defaultLevelId = $defaultLevel ? $defaultLevel->id : 1;

        // 3. Khởi tạo UserMembership nếu chưa có
        return UserMembership::firstOrCreate(
            ['user_id' => $user->id],
            [
                'level_id' => $defaultLevelId,
                'points' => 0,
                'total_spent' => 0,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Tích Coin tự động khi booking thanh toán thành công (Tỷ lệ 10.000đ = 1 Coin).
     */
    public function awardBookingCoin(Booking $booking): ?PointTransaction
    {
        if (!$booking->user_id) {
            return null;
        }

        // Chống tích coin trùng 2 lần cho cùng 1 đơn hàng
        $alreadyAwarded = PointTransaction::where('booking_id', $booking->id)
            ->where('type', 'EARN')
            ->exists();

        if ($alreadyAwarded) {
            return null;
        }

        $amount = (float) ($booking->final_amount ?? $booking->total_price ?? 0);
        $earnedCoin = (int) floor($amount / 10000); // 10.000đ = 1 Coin

        if ($earnedCoin <= 0) {
            return null;
        }

        return DB::transaction(function () use ($booking, $earnedCoin, $amount) {
            // 1. Cộng Coin vào ví
            $coin = Coin::firstOrCreate(['user_id' => $booking->user_id], ['balance' => 0]);
            $coin->increment('balance', $earnedCoin);

            // 2. Ghi nhật ký lịch sử PointTransaction
            $transaction = PointTransaction::create([
                'user_id'    => $booking->user_id,
                'booking_id' => $booking->id,
                'points'     => $earnedCoin,
                'type'       => 'EARN',
                'created_at' => now(),
            ]);

            // 3. Cập nhật tổng chi tiêu và tự động tính lại Hạng
            $userMembership = UserMembership::where('user_id', $booking->user_id)->first();
            if ($userMembership) {
                $userMembership->increment('total_spent', $amount);
            }

            $this->recalculateLevel($booking->user_id);

            return $transaction;
        });
    }

    /**
     * Tự động tính lại Hạng và gia hạn thời gian duy trì hạng (6 tháng) dựa trên số Coin tích lũy.
     */
    public function recalculateLevel(int $userId): void
    {
        $coin = Coin::where('user_id', $userId)->first();
        $balance = $coin ? $coin->balance : 0;

        $levels = MembershipLevel::orderBy('min_points', 'asc')->get();
        $matchedLevel = $levels->where('min_points', '<=', $balance)->last() ?? $levels->first();

        if (!$matchedLevel) {
            return;
        }

        $userMembership = UserMembership::where('user_id', $userId)->first();
        if ($userMembership) {
            $isLevelUp = ($matchedLevel->id != $userMembership->level_id);

            $userMembership->update([
                'level_id' => $matchedLevel->id,
                'level_expired_at' => now()->addMonths(6), // Gia hạn hạng 6 tháng
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Truy xuất danh sách Voucher của Customer kèm trạng thái (Chưa dùng, Đã dùng, Hết hạn) và HSD.
     */
    public function getUserVouchers(User $user)
    {
        $allVouchers = Voucher::where('status', 'ACTIVE')->orderBy('end_date', 'asc')->get();
        $usedVoucherIds = VoucherUsage::where('user_id', $user->id)->pluck('voucher_id')->toArray();
        $today = Carbon::today();

        return $allVouchers->map(function ($voucher) use ($usedVoucherIds, $today) {
            $isUsed = in_array($voucher->id, $usedVoucherIds);
            $isExpired = $voucher->end_date && $today->greaterThan($voucher->end_date);

            if ($isUsed) {
                $statusState = 'USED';
                $statusLabel = 'Đã sử dụng';
            } elseif ($isExpired) {
                $statusState = 'EXPIRED';
                $statusLabel = 'Hết hạn';
            } else {
                $statusState = 'AVAILABLE';
                $statusLabel = 'Chưa dùng';
            }

            $daysRemaining = null;
            if ($voucher->end_date && !$isUsed && !$isExpired) {
                $daysRemaining = max(0, (int) $today->diffInDays($voucher->end_date, false));
            }

            $voucher->status_state = $statusState;
            $voucher->status_label = $statusLabel;
            $voucher->days_remaining = $daysRemaining;

            return $voucher;
        });
    }
}