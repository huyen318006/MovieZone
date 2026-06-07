<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VNPAY - Thông tin cấu hình kết nối
    |--------------------------------------------------------------------------
    |
    | vnp_TmnCode: Mã website của merchant trên hệ thống VNPAY (8 ký tự).
    | vnp_HashSecret: Chuỗi bí mật dùng để tạo checksum (HMAC SHA512).
    | vnp_Url: URL cổng thanh toán VNPAY.
    | vnp_ReturnUrl: URL nhận kết quả thanh toán (redirect trình duyệt).
    | vnp_ApiUrl: URL API truy vấn / hoàn tiền giao dịch.
    |
    */

    'vnp_TmnCode'    => env('VNPAY_TMN_CODE', ''),
    'vnp_HashSecret' => env('VNPAY_HASH_SECRET', ''),
    'vnp_Url'        => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'vnp_ReturnUrl'  => env('VNPAY_RETURN_URL', 'http://localhost:8000/api/vnpay/return'),
    'vnp_ApiUrl'     => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),

    /*
    |--------------------------------------------------------------------------
    | Phiên bản API
    |--------------------------------------------------------------------------
    */
    'vnp_Version' => '2.1.0',
    'vnp_Command' => 'pay',
    'vnp_CurrCode' => 'VND',
    'vnp_Locale' => 'vn',

    /*
    |--------------------------------------------------------------------------
    | Thời gian hết hạn thanh toán (phút)
    |--------------------------------------------------------------------------
    */
    'vnp_ExpireMinutes' => 15,

];
