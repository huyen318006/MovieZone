<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Support\Carbon;

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