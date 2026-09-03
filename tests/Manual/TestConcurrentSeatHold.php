<?php
/**
 * Test thủ công: 2 user tranh giữ cùng 1 ghế đồng thời
 *
 * Chạy: php tests/Manual/TestConcurrentSeatHold.php
 *
 * Yêu cầu: Laravel đang chạy tại http://localhost
 *          Có ít nhất 1 showtime đang OPEN
 *          Redis hoặc cache đang hoạt động
 */

$baseUrl    = 'http://localhost'; // Đổi nếu cần
$showtimeId = 1;                  // ← Đổi thành showtime_id thật
$seatId     = 5;                  // ← Đổi thành showtime_seat_id thật (bảng showtime_seats)

// Cookie session của 2 user khác nhau (lấy từ browser DevTools → Application → Cookies → laravel_session)
$cookieUserA = 'laravel_session=AAAAA...'; // ← Thay bằng cookie thật của User A
$cookieUserB = 'laravel_session=BBBBB...'; // ← Thay bằng cookie thật của User B

echo "=== TEST: 2 User Giữ Cùng 1 Ghế Đồng Thời ===\n";
echo "Showtime: $showtimeId | Seat: $seatId\n\n";

/**
 * Gọi AJAX holdSeat giống frontend
 */
function holdSeat(string $baseUrl, int $showtimeId, int $seatId, string $cookie, string $label): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "$baseUrl/booking/hold-seat",
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'showtime_id' => $showtimeId,
            'seat_id'     => $seatId,
            'action'      => 'hold',
            '_token'      => 'CSRF_BYPASS', // Nếu route có CSRF, cần lấy token thật
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Cookie: ' . $cookie,
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true) ?? ['raw' => $response];
    echo "[$label] HTTP $httpCode → " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    return $data;
}

// ── TEST 1: Gọi tuần tự (A trước, B sau) ──────────────────────────────────────
echo "--- TEST 1: Tuần tự (A → B) ---\n";
holdSeat($baseUrl, $showtimeId, $seatId, $cookieUserA, 'User A');
holdSeat($baseUrl, $showtimeId, $seatId, $cookieUserB, 'User B');
// Mong đợi: A → success:true | B → success:false (HELD)

echo "\n--- Reset: giải phóng ghế của A ---\n";
// Gọi release cho User A (hoặc xóa cache thủ công)
$ch = curl_init("$baseUrl/booking/hold-seat");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query(['showtime_id' => $showtimeId, 'seat_id' => $seatId, 'action' => 'release', '_token' => 'CSRF_BYPASS']),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Cookie: ' . $cookieUserA, 'X-Requested-With: XMLHttpRequest'],
]);
curl_exec($ch);
curl_close($ch);
echo "Đã gửi release cho User A.\n\n";

// ── TEST 2: Gọi đồng thời bằng curl_multi ─────────────────────────────────────
echo "--- TEST 2: Đồng thời (A và B cùng lúc) ---\n";

$mh = curl_multi_init();
$handles = [];

foreach (['A' => $cookieUserA, 'B' => $cookieUserB] as $label => $cookie) {
    $ch = curl_init("$baseUrl/booking/hold-seat");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'showtime_id' => $showtimeId,
            'seat_id'     => $seatId,
            'action'      => 'hold',
            '_token'      => 'CSRF_BYPASS',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Cookie: ' . $cookie,
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json',
        ],
    ]);
    $handles[$label] = $ch;
    curl_multi_add_handle($mh, $ch);
}

// Chạy đồng thời
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

// Đọc kết quả
foreach ($handles as $label => $ch) {
    $response = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $data     = json_decode($response, true) ?? ['raw' => $response];
    echo "[$label] HTTP $httpCode → " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    curl_multi_remove_handle($mh, $ch);
}
curl_multi_close($mh);

// Kiểm tra kết quả
echo "\n=== Kết quả mong đợi ===\n";
echo "✅ Đúng: Đúng 1 trong 2 user có success:true, user còn lại có error_type:HELD\n";
echo "❌ Sai:  Cả 2 đều success:true → race condition! Cache::add() bị lỗi\n";
