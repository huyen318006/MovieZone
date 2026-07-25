<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherUsage;

class VoucherService
{
    public function applyVoucher(
        string $code,
        float $orderTotal,
        int $userId
    ): array {

        $voucher = Voucher::where('code', strtoupper(trim($code)))
            ->first();

        if (!$voucher) {
            return [
                'success' => false,
                'message' => 'Voucher không hợp lệ'
            ];
        }

        if ($voucher->status !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Voucher hiện không khả dụng'
            ];
        }

        if (
            now()->lt($voucher->start_date) ||
            now()->gt($voucher->end_date)
        ) {
            return [
                'success' => false,
                'message' => 'Voucher đã hết hạn'
            ];
        }

        if ($orderTotal < $voucher->min_order_amount) {
            return [
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu'
            ];
        }

        $totalUsage = VoucherUsage::where(
            'voucher_id',
            $voucher->id
        )->count();

        // usage_limit: -1 => unlimited, otherwise positive integer cap
        if (
            $voucher->usage_limit !== -1 &&
            $voucher->usage_limit > 0 &&
            $totalUsage >= $voucher->usage_limit
        ) {
            return [
                'success' => false,
                'message' => 'Voucher đã hết lượt sử dụng'
            ];
        }

        $userUsage = VoucherUsage::where(
            'voucher_id',
            $voucher->id
        )
            ->where('user_id', $userId)
            ->count();

        if (
            $voucher->usage_per_user > 0 &&
            $userUsage >= $voucher->usage_per_user
        ) {
            return [
                'success' => false,
                'message' => 'Bạn đã dùng hết số lượt của voucher này'
            ];
        }

        $discount = 0;

        if ($voucher->discount_type === 'FIXED') {

            $discount = $voucher->discount_value;

        } else {

            $discount =
                ($orderTotal * $voucher->discount_value) / 100;

            if (
                $voucher->max_discount &&
                $discount > $voucher->max_discount
            ) {
                $discount = $voucher->max_discount;
            }
        }

        $discount = min($discount, $orderTotal);

        return [
            'success' => true,
            'voucher' => $voucher,
            'discount' => $discount,
            'total' => $orderTotal - $discount
        ];
    }
}