# TODO: Sửa chọn ghế Staff giống Customer

## Step 1: BookTicketsController::sell_seat()
- [x] Thêm dữ liệu timer (holdExpiresAt, serverTime, holdTotalSeconds) truyền vào view

## Step 2: staff/sell-tickets-seats.blade.php
- [x] Thêm HTML timer box + CSS
- [x] Thêm HTML modal hết giờ + CSS
- [x] Thêm giới hạn MAX_SEATS = 10 trong JS
- [x] Bắt serverTime/expiresAt từ syncSeatHold để chạy timer
- [x] Đổi text nút: "Vui lòng chọn ghế" (disabled) → "Tiếp tục" (enabled)
- [x] Thêm kiểm tra lỗi ghế lẻ khi submit form

## Step 4: Fix màu ghế khi chọn (bến staff)
- [x] `StaffBookingService::sell_seat()`: ghế do chính staff giữ → render `HELD_BY_ME` (xanh lá, bỏ chọn được)
- [x] Blade: tự khôi phục ghế `HELD_BY_ME` từ server vào map selectedSeats
- [x] Ghế bị người khác giữ → `HELD` (cam, không tương tác), ghế đã bán → `SOLD` (xám)

## Step 3: Kiểm thử
- [ ] Chạy staff, chọn ghế, kiểm tra đồng hồ đếm ngược
- [ ] Kiểm tra modal hết giờ, giới hạn 10 ghế, lỗi ghế lẻ
- [ ] Chọn ghế → xanh lá, bấm lại → bỏ chọn được
- [ ] Ghế người khác giữ → cam, ghế đã bán → xám
