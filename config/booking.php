<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seat Hold Configuration
    |--------------------------------------------------------------------------
    |
    | Thời gian giữ ghế (phút) khi khách hàng bắt đầu chọn ghế.
    | Sau thời gian này, ghế sẽ tự động được giải phóng cho khách khác.
    |
    */

    'hold_minutes' => (int) env('BOOKING_HOLD_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Countdown Warning Threshold
    |--------------------------------------------------------------------------
    |
    | Số giây còn lại khi countdown chuyển sang chế độ cảnh báo (danger mode).
    | UI sẽ đổi màu đỏ, nhấp nháy để thông báo sắp hết thời gian.
    |
    */

    'warning_seconds' => 60,

];
