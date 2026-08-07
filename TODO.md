# TODO: Sửa validate chọn ghế & layout ghế (Customer)

## Thông tin
- **Max ghế/1 lần đặt:** 10 ghế (1 hàng)
- **Layout:** bỏ lối đi (aisle) cho view customer, staff KHÔNG đụng
- **Validate lẻ ghế:** đồng bộ frontend & backend (chỉ chặn khi lựa chọn khách tạo ra ghế trống cô lập)
- **Thông báo:** đổi "E2: Ghế đang được người khác giữ" → thân thiện

## Trạng thái: HOÀN THÀNH ✅

### 1. BookingController.php
- [x] Thêm hằng số `MAX_SEATS_PER_BOOKING = 10`
- [x] `showSeats` — bỏ lối đi ghế (`is_aisle => false`) cho view khách, nhóm không bị tách
- [x] `submitSeats` — thêm validate `max:10` (BR: tối đa 10 ghế)
- [x] `holdSeat` — sửa thông báo E2 thành "Rất tiếc, ghế này đã được người khác giữ..."
- [x] `hasSingleSeatGap` — đồng bộ logic: chỉ báo lỗi khi ghế trống cô lập do CHÍNH ghế khách chọn (SELECTED) gây ra, không báo khi bị chặn bởi ghế người khác (SOLD/HELD/BLOCKED)

### 2. resources/views/booking/seat.blade.php
- [x] JS chặn chọn quá 10 ghế, đếm đúng số ghế THỰC SỰ (kể cả ghế COUPLE gộp 2 ghế)
- [x] Giữ nguyên validate lẻ ghế frontend (đã đồng bộ với backend)

### 3. Admin (nhiệm vụ phụ — đánh số lại ghế sau khi xóa)
- [x] `SeatManageController` — khôi phục `renumberRowSeats()` + `applySeatNumber()` để đánh số ghế liền mạch (1,2,3...) sau khi xóa ghế
- [x] Xử lý tránh lỗi `Duplicate entry` (unique room_id + seat_code): đổi seat_code ghế đã xóa sang mã tạm `DEL_<id>` trước khi đánh số lại
- [x] `store` / `storeBatch` — luôn khôi phục `seat_code` đúng khi restore ghế đã xóa (không giữ mã `DEL_...`)

## Kiểm tra
- [x] `php -l` tất cả file PHP đã sửa → không lỗi cú pháp
</content>
