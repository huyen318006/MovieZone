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

            return $transaction;
        });
    }

    /**
     * Quét và xử lý tự động các tài khoản Membership quá hạn duy trì 6 tháng.
     */
    public function processExpiredMemberships(): array
    {
        $expiredMemberships = UserMembership::with('level')
            ->whereNotNull('level_expired_at')
            ->where('level_expired_at', '<', now())
            ->get();

        $processedCount = $expiredMemberships->count();
        $extendedCount = 0;
        $downgradedCount = 0;

        foreach ($expiredMemberships as $m) {
            // Kiểm tra trong 6 tháng qua khách có mua vé thành công không
            $hasActiveBooking = Booking::where('user_id', $m->user_id)
                ->where('status', 'PAID')
                ->where('created_at', '>=', now()->subMonths(6))
                ->exists();

            if ($hasActiveBooking) {
                // Khách có mua vé -> Gia hạn giữ nguyên hạng thêm 6 tháng
                $m->update(['level_expired_at' => now()->addMonths(6)]);
                $extendedCount++;
            } else {
                // Khách không mua vé -> Tự động hạ 1 mốc hạng (nếu chưa phải BRONZE)
                $currentLevel = $m->level;
                if ($currentLevel) {
                    $lowerLevel = MembershipLevel::where('min_points', '<', $currentLevel->min_points)
                        ->orderBy('min_points', 'desc')
                        ->first();

                    if ($lowerLevel) {
                        $oldLevelId = $m->level_id;
                        $m->update([
                            'level_id' => $lowerLevel->id,
                            'level_expired_at' => now()->addMonths(6),
                            'updated_at' => now(),
                        ]);

                        MembershipLevelHistory::create([
                            'user_id'      => $m->user_id,
                            'old_level_id' => $oldLevelId,
                            'new_level_id' => $lowerLevel->id,
                            'reason'       => 'Tự động hạ 1 mốc hạng do không phát sinh giao dịch mua vé trong 6 tháng',
                        ]);

                        $downgradedCount++;
                    } else {
                        // Đã ở mốc thấp nhất (BRONZE) -> Gia hạn tiếp 6 tháng
                        $m->update(['level_expired_at' => now()->addMonths(6)]);
                        $extendedCount++;
                    }
                }
            }
        }

        return [
            'processed'  => $processedCount,
            'extended'   => $extendedCount,
            'downgraded' => $downgradedCount,
        ];
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

        // Tính coin thưởng dựa trên tổng giá trị gốc (vé + combo, TRƯỚC giảm giá)
        // để user luôn được thưởng coin kể cả khi thanh toán 0đ (xu cover 100%)
        $amount = (float) (($booking->total_ticket_amount ?? 0) + ($booking->total_combo_amount ?? 0));
        if ($amount <= 0) {
            return null;
        }

        // Lấy Hạng hiện tại của User để xác định % tích Coin thưởng
        $userMembership = UserMembership::with('level')->where('user_id', $booking->user_id)->first();
        $levelName = strtoupper($userMembership?->level?->name ?? 'BRONZE');

        // Tỷ lệ tích Coin thưởng theo % Hạng:
        // BRONZE: 1% | SILVER: 3% | GOLD: 5% | PLATINUM: 7% | DIAMOND: 10%
        $earnRates = [
            'BRONZE'   => 1,
            'SILVER'   => 3,
            'GOLD'     => 5,
            'PLATINUM' => 7,
            'DIAMOND'  => 10,
        ];
        $ratePercent = $earnRates[$levelName] ?? 1;

        $earnedCoin = (int) round(($amount * $ratePercent) / 100);

        if ($earnedCoin <= 0) {
            return null;
        }

        return DB::transaction(function () use ($booking, $earnedCoin, $amount, $levelName, $ratePercent) {
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

            // 4. Lưu thông tin coin thưởng vào SepayOrder metadata để hiển thị trên hóa đơn/email
            $sepayOrder = \App\Models\SepayOrder::where('booking_id', $booking->id)->first();
            if ($sepayOrder) {
                $meta = $sepayOrder->metadata ?? [];
                $meta['coin_earned'] = $earnedCoin;
                $meta['coin_earn_rate'] = $ratePercent;
                $meta['membership_level'] = $levelName;
                $meta['coin_new_balance'] = $coin->fresh()->balance;
                $sepayOrder->update(['metadata' => $meta]);
            }

            return $transaction;
        });
    }

    /**
     * Tự động thăng Hạng và gia hạn thời gian duy trì hạng (6 tháng) khi mua vé tích lũy đủ chi tiêu.
     */
    public function recalculateLevel(int $userId, string $reason = 'Thăng hạng tự động khi tích lũy mua vé'): void
    {
        $userMembership = UserMembership::with('level')->where('user_id', $userId)->first();
        if (!$userMembership) {
            return;
        }

        $totalSpent = (float) $userMembership->total_spent;
        $levels = MembershipLevel::orderBy('min_points', 'asc')->get();
        $matchedLevel = $levels->where('min_points', '<=', $totalSpent)->last() ?? $levels->first();

        if (!$matchedLevel) {
            return;
        }

        $currentLevel = $userMembership->level;
        $oldLevelId = $userMembership->level_id;

        // Chỉ tự động THĂNG HẠNG khi mốc mới cao hơn mốc hiện tại
        if (!$currentLevel || (float)$matchedLevel->min_points > (float)$currentLevel->min_points) {
            $userMembership->update([
                'level_id' => $matchedLevel->id,
                'level_expired_at' => now()->addMonths(6),
                'updated_at' => now(),
            ]);

            if ($oldLevelId != $matchedLevel->id) {
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
     * Truy xuất danh sách Voucher của Customer phân quyền mở khóa theo Hạng thành viên hiện tại.
     */
    public function getUserVouchers(User $user)
    {
        $userMembership = UserMembership::with('level')->where('user_id', $user->id)->first();
        $levelName = strtoupper($userMembership?->level?->name ?? 'BRONZE');

        // Danh sách mốc Voucher mở khóa theo Hạng
        $allowedPrefixes = match($levelName) {
            'SILVER'   => ['BRONZE', 'SILVER', 'WELCOME', 'MOVIE'],
            'GOLD'     => ['BRONZE', 'SILVER', 'GOLD', 'WELCOME', 'MOVIE'],
            'PLATINUM' => ['BRONZE', 'SILVER', 'GOLD', 'PLATINUM', 'WELCOME', 'MOVIE'],
            'DIAMOND'  => ['BRONZE', 'SILVER', 'GOLD', 'PLATINUM', 'DIAMOND', 'WELCOME', 'MOVIE'],
            default    => ['BRONZE', 'WELCOME', 'MOVIE'],
        };

        $allVouchers = Voucher::where('status', 'ACTIVE')
            ->get()
            ->filter(function ($voucher) use ($allowedPrefixes) {
                foreach ($allowedPrefixes as $prefix) {
                    if (str_starts_with(strtoupper($voucher->code), $prefix)) {
                        return true;
                    }
                }
                return false;
            })
            ->sortBy('end_date')
            ->values();

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