<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_code',
        'sepay_order_id',
        'customer_email',
        'customer_name',
        'movie_title',
        'cinema',
        'room',
        'showtime',
        'show_date',
        'format',
        'seats',
        'total_amount',
        'payment_method',
        'transaction_id',
        'paid_at',
        'email_status',
        'email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'seats'         => 'array',
            'total_amount'  => 'integer',
            'paid_at'       => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function sepayOrder()
    {
        return $this->belongsTo(SepayOrder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Sinh mã hoá đơn unique
     */
    public static function generateInvoiceCode(): string
    {
        do {
            $code = 'INV-' . strtoupper(Str::random(8));
        } while (self::where('invoice_code', $code)->exists());

        return $code;
    }

    /**
     * Tạo hoá đơn từ SepayOrder đã thanh toán
     */
    public static function createFromOrder(SepayOrder $order): self
    {
        $metadata = $order->metadata ?? [];

        return self::create([
            'invoice_code'   => self::generateInvoiceCode(),
            'sepay_order_id' => $order->id,
            'customer_email' => $metadata['customer_email'] ?? '',
            'customer_name'  => $metadata['customer_name'] ?? null,
            'movie_title'    => $metadata['movie_title'] ?? '',
            'cinema'         => $metadata['cinema'] ?? '',
            'room'           => $metadata['room'] ?? '',
            'showtime'       => $metadata['showtime'] ?? '',
            'show_date'      => $metadata['show_date'] ?? '',
            'format'         => $metadata['format'] ?? '2D',
            'seats'          => $metadata['seats'] ?? [],
            'total_amount'   => $order->amount,
            'payment_method' => 'QR Bank Transfer',
            'transaction_id' => $order->transaction_id,
            'paid_at'        => $order->paid_at,
            'email_status'   => 'pending',
        ]);
    }

    public static function createRetailFromOrder(SepayOrder $order): self
    {
        $metadata = $order->metadata ?? [];

        return self::create([
            'invoice_code'   => self::generateInvoiceCode(),
            'sepay_order_id' => $order->id,
            'customer_email' => $metadata['customer_email'] ?? '',
            'customer_name'  => $metadata['customer_name'] ?? null,
            'movie_title'    => 'Bán sản phẩm lẻ',
            'cinema'         => '',
            'room'           => '',
            'showtime'       => '',
            'show_date'      => '',
            'format'         => 'Retail',
            'seats'          => ['items' => $metadata['items'] ?? []],
            'total_amount'   => $order->amount,
            'payment_method' => ($metadata['payment_method'] ?? 'ONLINE') === 'CASH'
                ? 'Tiền mặt'
                : 'Thanh toán online',
            'transaction_id' => $order->transaction_id,
            'paid_at'        => $order->paid_at,
            'email_status'   => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Format số tiền VND
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->total_amount, 0, ',', '.') . 'đ';
    }

    /**
     * Lấy danh sách mã ghế
     */
    public function getSeatCodesAttribute(): string
    {
        $seats = $this->seats ?? [];
        return implode(', ', array_column($seats, 'code'));
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Đánh dấu email đã gửi thành công
     */
    public function markEmailSent(): void
    {
        $this->update([
            'email_status'  => 'sent',
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Đánh dấu email gửi thất bại
     */
    public function markEmailFailed(): void
    {
        $this->update([
            'email_status' => 'failed',
        ]);
    }
}
