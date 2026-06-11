<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SepayOrder extends Model
{
    protected $table = 'sepay_orders';

    protected $fillable = [
        'order_code',
        'package_id',
        'package_name',
        'amount',
        'status',
        'transaction_id',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Đánh dấu đơn hàng đã thanh toán
     */
    public function markAsPaid(string $transactionId, ?array $transactionData = null): void
    {
        $updateData = [
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ];

        if ($transactionData) {
            $metadata = $this->metadata ?? [];
            $metadata['sepay_transaction'] = $transactionData;
            $updateData['metadata'] = $metadata;
        }

        $this->update($updateData);
    }

    /**
     * Đánh dấu đơn hàng hết hạn
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Kiểm tra đơn hàng đã hết hạn chưa
     */
    public function isExpired(): bool
    {
        $expiryMinutes = config('sepay.order_expiry_minutes', 15);

        return $this->status === 'pending'
            && $this->created_at->addMinutes($expiryMinutes)->isPast();
    }

    /**
     * Kiểm tra đơn hàng đã thanh toán chưa
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Lấy thời gian hết hạn
     */
    public function getExpiresAt(): Carbon
    {
        $expiryMinutes = config('sepay.order_expiry_minutes', 15);

        return $this->created_at->addMinutes($expiryMinutes);
    }

    /**
     * Format số tiền thành chuỗi VND
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.') . 'đ';
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Lấy thông tin booking từ metadata
     */
    public function getBookingInfo(string $key, $default = '')
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Lấy danh sách ghế đã chọn
     */
    public function getBookingSeats(): array
    {
        return $this->metadata['seats'] ?? [];
    }

    /**
     * Lấy danh sách mã ghế (A1, B2, VIP1...)
     */
    public function getSeatCodesFormatted(): string
    {
        $seats = $this->getBookingSeats();
        return implode(', ', array_column($seats, 'code'));
    }

    /**
     * Kiểm tra đây có phải đơn booking vé phim không
     */
    public function isBookingOrder(): bool
    {
        return $this->package_id === 'booking';
    }

    /**
     * Relationship: Hoá đơn liên kết
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
