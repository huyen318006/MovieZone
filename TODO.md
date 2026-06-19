# TODO

## Mục tiêu
Đổi quản lý ghế: admin tạo ghế theo `seat_type` (STANDARD/VIP/COUPLE) và giá được lấy tự động từ database (`ticket_prices`), không còn nhập `price` thủ công.

## Checklist
- [x] Update `app/Http/Controllers/Admin/SeatManageController.php`
  - [x] Thêm hàm lấy price theo `seat_type` từ `ticket_prices`
  - [x] Sửa `store`, `update`, `storeBatch` để bỏ validate `price` + tự set `seat->price`
- [x] Update UI admin
  - [x] `resources/views/admin/seats/index.blade.php`: bỏ input `price` trong modal “Tạo nhiều ghế theo hàng”
  - [x] `resources/views/admin/seats/create.blade.php`: bỏ input `price`
  - [x] `resources/views/admin/seats/edit.blade.php`: bỏ input `price`
- [ ] Test luồng đặt vé
  - [ ] Vào admin tạo 1-10 ghế STANDARD/VIP/COUPLE, confirm giá hiển thị đúng
  - [ ] Tới booking/seat, tổng tiền tính đúng theo `showtime_seats.price`

