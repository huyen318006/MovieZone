<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SePay Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    // Merchant credentials (Payment Gateway)
    'merchant_id' => env('SEPAY_MERCHANT_ID', ''),
    'secret_key' => env('SEPAY_SECRET_KEY', ''),

    // API Token (User API v2 - for checking transactions)
    'api_token' => env('SEPAY_API_TOKEN', ''),

    // Bank account info (for QR code generation)
    'bank_account' => env('SEPAY_BANK_ACCOUNT', ''),
    'bank_code' => env('SEPAY_BANK_CODE', 'MBBank'),

    // Environment: 'sandbox' or 'production'
    'environment' => env('SEPAY_ENVIRONMENT', 'sandbox'),

    // API endpoints
    'api_url' => 'https://my.sepay.vn/userapi/transactions/list',

    // Order settings
    'order_prefix' => 'DH',          // Prefix cho mã đơn hàng
    'order_expiry_minutes' => 5,      // Timer thanh toán riêng 5 phút (tách biệt với timer giữ ghế 5 phút, tổng = 10 phút)
    'polling_interval' => 5000,       // Polling interval (ms)

    /*
    |--------------------------------------------------------------------------
    | Payment Packages - Tuỳ chỉnh tại đây
    |--------------------------------------------------------------------------
    |
    | Thêm, sửa, xoá các gói thanh toán bằng cách chỉnh sửa mảng bên dưới.
    | Mỗi gói cần có: id, name, description, amount (VND), icon, color
    |
    */

    'packages' => [
        [
            'id' => 'basic',
            'name' => 'Gói Cơ Bản',
            'description' => 'Nạp tiền cơ bản',
            'amount' => 10000,
            'icon' => '💎',
            'color' => '#6366f1',
            'features' => [
                'Nạp nhanh 10.000đ',
                'Xác nhận tự động',
            ],
        ],
        [
            'id' => 'standard',
            'name' => 'Gói Tiêu Chuẩn',
            'description' => 'Nạp tiền tiêu chuẩn',
            'amount' => 15000,
            'icon' => '⭐',
            'color' => '#8b5cf6',
            'popular' => true, // Gói được đề xuất
            'features' => [
                'Nạp nhanh 15.000đ',
                'Xác nhận tự động',
                'Phổ biến nhất',
            ],
        ],
        [
            'id' => 'premium',
            'name' => 'Gói Nâng Cao',
            'description' => 'Nạp tiền nâng cao',
            'amount' => 20000,
            'icon' => '👑',
            'color' => '#ec4899',
            'features' => [
                'Nạp nhanh 20.000đ',
                'Xác nhận tự động',
                'Giá trị tốt nhất',
            ],
        ],
    ],

];
