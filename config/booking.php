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

    'hold_minutes' => (int) env('BOOKING_HOLD_MINUTES', 5),

];
