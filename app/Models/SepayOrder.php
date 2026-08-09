<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SepayOrder extends Model
{
    protected $table = 'sepay_orders';

    protected $fillable = [
        'order_code',
        'booking_id',
        'package_id',
        'package_name',
        'amount',
        'status',
        'transaction_id',
        'paid_at',
        'metadata',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

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
        if ($this->status === 'paid') {
            return false;
        }

        return $this->status === 'expired' || $this->getExpiresAt()->isPast();
    }

    /**
     * Kiểm tra đơn hàng đã thanh toán chưa
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Lấy thời gian hết hạn (Ưu tiên lấy expired_at từ booking)
     */
    public function getExpiresAt(): Carbon
    {
        if ($this->booking && $this->booking->expired_at) {
            return Carbon::parse($this->booking->expired_at);
        }

        $expiryMinutes = config('sepay.order_expiry_minutes', 5);

        return $this->created_at ? $this->created_at->copy()->addMinutes($expiryMinutes) : now()->addMinutes($expiryMinutes);
    }

    /**
     * Format số tiền thành chuỗi VND
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.').'đ';
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
     * Kiểm tra email đã gửi chưa
     */
    public function isEmailSent(): bool
    {
        return ! empty($this->metadata['email_sent']);
    }

    /**
     * Lấy email khách hàng từ metadata hoặc từ booking->user
     */
    public function getCustomerEmail(): string
    {
        // Ưu tiên email trong metadata
        if (! empty($this->metadata['customer_email'])) {
            return $this->metadata['customer_email'];
        }

        // Lấy từ bảng booking nếu có
        if ($this->booking && ! empty($this->booking->customer_email)) {
            return $this->booking->customer_email;
        }

        // Nếu là đơn do staff đặt, không lấy email của staff
        if (isset($this->metadata['booked_by']) && $this->metadata['booked_by'] === 'staff') {
            return '';
        }

        // Fallback: lấy từ booking->user cho khách hàng tự đặt
        if ($this->booking && $this->booking->user) {
            return $this->booking->user->email ?? '';
        }

        return '';
    }

    /**
     * Lấy tên khách hàng từ metadata hoặc từ booking->user
     */
    public function getCustomerName(): string
    {
        if (! empty($this->metadata['customer_name'])) {
            return $this->metadata['customer_name'];
        }

        if ($this->booking && ! empty($this->booking->customer_name)) {
            return $this->booking->customer_name;
        }

        if (isset($this->metadata['booked_by']) && $this->metadata['booked_by'] === 'staff') {
            return '';
        }

        if ($this->booking && $this->booking->user) {
            return $this->booking->user->name ?? '';
        }

        return '';
    }

    /**
     * Lấy số điện thoại khách hàng từ metadata hoặc từ booking->user
     */
    public function getCustomerPhone(): string
    {
        if (! empty($this->metadata['customer_phone'])) {
            return $this->metadata['customer_phone'];
        }

        if ($this->booking && ! empty($this->booking->customer_phone)) {
            return $this->booking->customer_phone;
        }

        if (isset($this->metadata['booked_by']) && $this->metadata['booked_by'] === 'staff') {
            return '';
        }

        if ($this->booking && $this->booking->user) {
            return $this->booking->user->phone ?? '';
        }

        return '';
    }

    /**
     * Tạo chuỗi dữ liệu cho QR code hoá đơn (chuẩn mới MZ|...|...).
     */
    public function generateTicketQrData(): string
    {
        $qrService = new \App\Services\QRCodeService();
        $bookingCode = $this->booking ? $this->booking->booking_code : $this->getBookingInfo('booking_code', $this->order_code);
        return $qrService->generateQRContent($bookingCode);
    }

    /**
     * Tạo dữ liệu JSON chi tiết (backward compatibility hoặc dùng cho hiển thị khác).
     * Chứa đầy đủ thông tin vé: mã hoá đơn, tên phim, rạp, phòng, thời gian, ghế, số lượng, combo
     */
    public function generateTicketQrJson(): string
    {
        $combos = $this->metadata['combos'] ?? [];
        $comboList = [];
        foreach ($combos as $combo) {
            $comboList[] = [
                'ten' => $combo['name'] ?? '',
                'so_luong' => $combo['quantity'] ?? 0,
            ];
        }

        $data = [
            'ma_hoa_don' => $this->order_code,
            'ten_phim' => $this->getBookingInfo('movie_title'),
            'rap_chieu' => $this->getBookingInfo('cinema'),
            'phong_chieu' => $this->getBookingInfo('room'),
            'thoi_gian_chieu' => $this->getBookingInfo('showtime').', '.$this->getBookingInfo('show_date'),
            'ghe_ngoi' => $this->getSeatCodesFormatted(),
            'so_luong' => $this->metadata['seat_count'] ?? count($this->getBookingSeats()),
            'combo' => $comboList,
            'tong_tien' => number_format($this->amount, 0, ',', '.').'đ',
            'trang_thai' => $this->isPaid() ? 'DA_THANH_TOAN' : 'CHO_THANH_TOAN',
            'thoi_gian_thanh_toan' => $this->paid_at ? $this->paid_at->format('d/m/Y H:i') : null,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
