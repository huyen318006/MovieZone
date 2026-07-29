<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Coin;
use App\Models\MembershipLevel;
use App\Models\MembershipLevelHistory;
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
                'level_expired_at' => now()->addMonths(6),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Admin điều chỉnh Coin thủ công (Cộng / Trừ) kèm lý do và ghi nhận Audit Log.
     */
    public function adjustCoinManually(User $targetUser, int $amount, string $actionType, string $reason, int $adminUserId): PointTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Số Coin điều chỉnh phải lớn hơn 0.');
        }

        $coin = Coin::firstOrCreate(['user_id' => $targetUser->id], ['balance' => 0]);
        $oldBalance = $coin->balance;

        if ($actionType === 'DEDUCT' && $amount > $oldBalance) {
            throw new \InvalidArgumentException("Số Coin muốn trừ (" . number_format($amount) . ") vượt quá số dư hiện tại (" . number_format($oldBalance) . ") của khách hàng.");
        }

        $delta = ($actionType === 'ADD') ? $amount : -$amount;
        $newBalance = $oldBalance + $delta;

        return DB::transaction(function () use ($targetUser, $coin, $oldBalance, $newBalance, $delta, $actionType, $amount, $reason, $adminUserId) {
            // 1. Cập nhật số dư Coin
            $coin->balance = $newBalance;
            $coin->save();

            // 2. Ghi nhật ký PointTransaction
            $transaction = PointTransaction::create([
                'user_id'    => $targetUser->id,
                'booking_id' => null,
                'points'     => $delta,
                'type'       => 'ADJUST',
                'created_at' => now(),
            ]);

            // 3. Ghi Audit Log cho hệ thống Admin
            AuditLog::create([
                'user_id'     => $adminUserId,
                'action'      => 'ADJUST_COIN',
                'entity_name' => 'UserCoin',
                'entity_id'   => $targetUser->id,
                'old_value'   => (string) $oldBalance,
                'new_value'   => (string) $newBalance . " (Lý do: {$reason})",
                'created_at'  => now(),
            ]);

            // 4. Tính toán lại Hạng của khách hàng
            $this->recalculateLevel($targetUser->id, "Admin điều chỉnh Coin thủ công ({$actionType} {$amount} Coin). Lý do: {$reason}");

            return $transaction;
        });
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

            $this->recalculateLevel($booking->user_id, 'Thăng hạng/Gia hạn tự động khi mua vé thành công');

            return $transaction;
        });
    }

    /**
     * Tự động tính lại Hạng và gia hạn thời gian duy trì hạng (6 tháng) dựa trên số Coin tích lũy.
     */
    public function recalculateLevel(int $userId, string $reason = 'Thay đổi hạng tự động'): void
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
            $oldLevelId = $userMembership->level_id;
            $isLevelChanged = ($matchedLevel->id != $oldLevelId);

            $userMembership->update([
                'level_id' => $matchedLevel->id,
                'level_expired_at' => now()->addMonths(6), // Gia hạn hạng 6 tháng
                'updated_at' => now(),
            ]);

            // Nếu có sự thay đổi hạng, ghi nhận lịch sử biến động Hạng
            if ($isLevelChanged) {
                MembershipLevelHistory::create([
                    'user_id'      => $userId,
                    'old_level_id' => $oldLevelId,
                    'new_level_id' => $matchedLevel->id,
                    'reason'       => $reason,
                ]);
            }
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