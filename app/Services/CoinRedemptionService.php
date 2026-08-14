<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoinRedemptionService
{
    /**
     * Tỷ lệ quy đổi: 1 Coin = ? VNĐ
     * Dễ dàng sửa đổi sau nếu cần thay đổi tỷ lệ.
     */
    const COIN_TO_VND = 1;

    /**
     * Tính số xu tối đa có thể sử dụng cho đơn hàng.
     *
     * @param int   $userId           ID khách hàng
     * @param float $amountAfterVoucher Số tiền còn lại sau khi đã áp voucher
     * @return array [balance, maxRedeemable, maxDiscountVND]
     */
    public function calculateMaxRedeemable(int $userId, float $amountAfterVoucher): array
    {
        $coin = Coin::where('user_id', $userId)->first();
        $balance = $coin ? (int) $coin->balance : 0;

        if ($balance <= 0 || $amountAfterVoucher <= 0) {
            return [
                'balance' => $balance,
                'maxRedeemable' => 0,
                'maxDiscountVND' => 0,
            ];
        }

        // Số xu tối đa = min(số dư, số tiền còn lại / tỷ lệ quy đổi)
        // Xu có thể giảm 100% số tiền còn lại (không giới hạn %)
        $maxByAmount = (int) floor($amountAfterVoucher / self::COIN_TO_VND);
        $maxRedeemable = min($balance, $maxByAmount);
        $maxDiscountVND = $maxRedeemable * self::COIN_TO_VND;

        return [
            'balance' => $balance,
            'maxRedeemable' => $maxRedeemable,
            'maxDiscountVND' => $maxDiscountVND,
        ];
    }

    /**
     * Áp dụng xu giảm giá vào session booking_tam.
     * CHỈ lưu intent vào session, CHƯA trừ xu trong DB.
     *
     * @param int   $userId     ID khách hàng
     * @param int   $coinAmount Số xu muốn dùng
     * @param array $bookingData Session booking_tam hiện tại
     * @return array ['success' => bool, 'message' => string, 'bookingData' => array]
     */
    public function applyCoinDiscount(int $userId, int $coinAmount, array $bookingData): array
    {
        $subtotal = ($bookingData['total_seat_amount'] ?? 0) + ($bookingData['total_combo_amount'] ?? 0);
        $tierDiscount = $bookingData['tier_discount_amount'] ?? 0;
        $voucherDiscount = $bookingData['discount_amount'] ?? 0;
        // H1-FIX: Phải trừ cả tier_discount_amount
        $amountAfterVoucher = max(0, $subtotal - $tierDiscount - $voucherDiscount);

        $maxInfo = $this->calculateMaxRedeemable($userId, $amountAfterVoucher);

        if ($coinAmount <= 0) {
            return [
                'success' => false,
                'message' => 'Số xu phải lớn hơn 0.',
                'bookingData' => $bookingData,
            ];
        }

        if ($coinAmount > $maxInfo['maxRedeemable']) {
            return [
                'success' => false,
                'message' => 'Số xu vượt quá mức cho phép. Tối đa có thể dùng: ' . number_format($maxInfo['maxRedeemable']) . ' xu.',
                'bookingData' => $bookingData,
            ];
        }

        $coinDiscountVND = $coinAmount * self::COIN_TO_VND;

        $bookingData['coin_used'] = $coinAmount;
        $bookingData['coin_discount_amount'] = $coinDiscountVND;
        $bookingData['total'] = max(0, $amountAfterVoucher - $coinDiscountVND);

        return [
            'success' => true,
            'message' => 'Áp dụng ' . number_format($coinAmount) . ' xu thành công! Giảm ' . number_format($coinDiscountVND) . 'đ.',
            'bookingData' => $bookingData,
        ];
    }

    /**
     * Xoá xu giảm giá khỏi session booking_tam.
     *
     * @param array $bookingData Session booking_tam hiện tại
     * @return array bookingData đã cập nhật
     */
    public function removeCoinDiscount(array $bookingData): array
    {
        $subtotal = ($bookingData['total_seat_amount'] ?? 0) + ($bookingData['total_combo_amount'] ?? 0);
        $tierDiscount = $bookingData['tier_discount_amount'] ?? 0;
        $voucherDiscount = $bookingData['discount_amount'] ?? 0;

        $bookingData['coin_used'] = 0;
        $bookingData['coin_discount_amount'] = 0;
        // H1-FIX: Phải trừ cả tier_discount_amount khi tính lại total
        $bookingData['total'] = max(0, $subtotal - $tierDiscount - $voucherDiscount);

        return $bookingData;
    }

    /**
     * Trừ xu thật trong DB khi thanh toán thành công.
     * PHẢI được gọi bên trong DB::transaction().
     *
     * @param int $userId    ID khách hàng
     * @param int $coinAmount Số xu cần trừ
     * @param int $bookingId  ID booking
     * @throws \Exception Nếu số dư không đủ
     */
    public function deductCoins(int $userId, int $coinAmount, int $bookingId): void
    {
        if ($coinAmount <= 0) {
            return;
        }

        // Chống trừ xu trùng lặp (giống pattern awardBookingCoin)
        $alreadyRedeemed = PointTransaction::where('booking_id', $bookingId)
            ->where('type', 'REDEEM')
            ->exists();

        if ($alreadyRedeemed) {
            Log::info('Coin already redeemed for booking, skipping', [
                'booking_id' => $bookingId,
                'user_id' => $userId,
            ]);
            return;
        }

        $coin = Coin::where('user_id', $userId)->first();

        if (!$coin || $coin->balance < $coinAmount) {
            Log::warning('Insufficient coin balance for redemption', [
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'coin_amount' => $coinAmount,
                'balance' => $coin ? $coin->balance : 0,
            ]);
            // Không throw exception — vẫn cho thanh toán thành công
            // nhưng skip trừ xu (edge case: xu bị trừ ở nơi khác giữa chừng)
            return;
        }

        // Trừ xu
        $coin->decrement('balance', $coinAmount);

        // Ghi nhật ký
        PointTransaction::create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'points' => -$coinAmount,
            'type' => 'REDEEM',
            'created_at' => now(),
        ]);

        Log::info('Coins redeemed for booking', [
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'coins_deducted' => $coinAmount,
            'new_balance' => $coin->fresh()->balance,
        ]);
    }

    /**
     * Hoàn xu khi huỷ đơn hàng.
     *
     * @param int $userId    ID khách hàng
     * @param int $coinAmount Số xu cần hoàn
     * @param int $bookingId  ID booking
     */
    public function refundCoins(int $userId, int $coinAmount, int $bookingId): void
    {
        if ($coinAmount <= 0) {
            return;
        }

        // Chống hoàn xu trùng lặp
        $alreadyRefunded = PointTransaction::where('booking_id', $bookingId)
            ->where('type', 'ADJUST')
            ->where('points', '>', 0)
            ->exists();

        if ($alreadyRefunded) {
            Log::info('Coins already refunded for booking, skipping', [
                'booking_id' => $bookingId,
            ]);
            return;
        }

        $coin = Coin::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
        $coin->increment('balance', $coinAmount);

        PointTransaction::create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'points' => $coinAmount,
            'type' => 'ADJUST',
            'created_at' => now(),
        ]);

        Log::info('Coins refunded for cancelled booking', [
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'coins_refunded' => $coinAmount,
            'new_balance' => $coin->fresh()->balance,
        ]);
    }
}
