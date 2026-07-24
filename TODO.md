# TỔNG HỢP NHỮNG GÌ ĐÃ SỬA

## 🎯 Vấn đề 1: Admin vào phòng chiếu không thấy ghế đang HELD/SOLD
**File:** `app/Http/Controllers/Admin/RoomManageController.php`
- ✅ Thêm `use Illuminate\Support\Facades\Cache;`
- ✅ Sửa method `seats(Request $request, Room $room)`:
  - Load danh sách suất chiếu sắp tới của phòng
  - Xử lý tham số `showtime_id` từ URL
  - Tính dynamic status (SOLD/HELD/BLOCKED/AVAILABLE) cho từng ghế dựa trên suất chiếu được chọn
  - Check booking_seats để biết ghế đã bán
  - Check Cache `seat_held_{showtime_id}_{showtime_seat_id}` để biết ghế đang giữ
  - Pass `$showtimes`, `$selectedShowtime` xuống view
    
## 🎯 Vấn đề 2: Sửa ghế validate khác xóa ghế
**File:** `app/Http/Controllers/Admin/SeatManageController.php`

### a) `assertSeatRoomNotLockedForRealtime()` — THÊM check suất chiếu sắp bắt đầu 30 phút
- GIỮ: check suất đã bắt đầu (`start_time <= now`)
- GIỮ: check booking không cancelled
- **THÊM:** check suất sắp bắt đầu trong 30 phút (`start_time > now` AND `start_time <= now()+30phút`)
- Nếu có suất chiếu sắp bắt đầu → throw exception "Phòng có suất chiếu sắp bắt đầu trong 30 phút"

### b) Method MỚI: `assertSeatNotUsed(Seat $seat)`
- Kiểm tra `showtimeSeats()` có `SOLD` hoặc `HELD` trong suất chiếu tương lai không
- Kiểm tra cache `seat_held_{showtime_id}_{showtime_seat_id}` của user khác
- Nếu có → throw exception "Ghế đang được khách đặt/giữ"

### c) `update()` — THÊM validate
- GIỮ: validate input, check trùng mã ghế, cập nhật giá, audit log
- **THÊM:** `assertSeatNotLockedForRealtime()` (chặn nếu phòng sắp chiếu)
- **THÊM:** `assertSeatNotUsed()` (chặn nếu ghế đang được dùng)
- **SỬA:** bỏ hardcode 'F', thay bằng `computeZones()` validation động (giống store/storeBatch)

### d) `destroy()` — THÊM validate
- GIỮ: `assertSeatNotLockedForRealtime()`, check showtimeSeats, xoá ghế cặp COUPLE
- **THÊM:** `assertSeatNotUsed()` (chặn xoá ghế đang được dùng)

### e) `toggleLock()` — THÊM + XOÁ
- GIỮ: `assertSeatNotLockedForRealtime()`, check BROKEN, toggle logic, sync COUPLE
- **THÊM:** `assertSeatNotUsed()`
- **XOÁ:** đoạn check `$heldBySomeone` thủ công (đã được thay bởi `assertSeatNotUsed()`)

### f) `destroyMany()` — THAY thế logic
- GIỮ: `assertSeatNotLockedForRealtime()` cho từng ghế
- **THAY:** đoạn check `$hasActiveUsage` + `$heldBySomeone` thủ công bằng `assertSeatNotUsed()`

### g) `toggleLockMany()` — THÊM validate
- GIỮ: `assertSeatRoomNotLockedForRealtime()`, toggle logic
- **THÊM:** `assertSeatNotUsed()` cho từng ghế trước khi toggle

### h) `bulkUpdateType()` — THÊM validate + BỎ zone restriction
- GIỮ: validate input, check phòng ACTIVE, `assertSeatRoomNotLockedForRealtime()`, validate maxRow, cập nhật giá, audit log
- **XOÁ:** zone restriction (không còn bắt buộc VIP phải đúng hàng VIP)
- **THÊM:** `assertSeatNotUsed()` cho từng ghế trong hàng

### i) `index()` — SỬA auto-select
- **XOÁ:** tự động chọn suất chiếu gần nhất khi load trang
- Chỉ set `selectedShowtime` khi người dùng CHỦ ĐỘNG chọn từ dropdown

## 🎯 Vấn đề 3: Đổi loại hàng ghế bị validate chặn
**File:** `app/Http/Controllers/Admin/SeatManageController.php`
- ✅ Xoá zone restriction trong `bulkUpdateType()` — cho phép đổi VIP↔STANDARD↔COUPLE
- ✅ Chỉ giữ: validate maxRow + check ghế không đang SOLD/HELD

## 🎯 Vấn đề 4: Màu ghế mặc định bị đổi sai
**File:** `resources/views/admin/seats/index.blade.php`
- ✅ Xoá class `dyn-available` (không còn override màu ghế AVAILABLE)
- ✅ Xoá CSS `.dyn-available` 
- ✅ Thêm comment "AVAILABLE giữ nguyên màu mặc định của ghế (ko thêm class)"
- ✅ Khi không chọn suất: ghế giữ màu gốc (STANDARD=xanh dương, VIP=vàng, COUPLE=hồng)
- ✅ Khi chọn suất: chỉ SOLD→đỏ, HELD→cam, BLOCKED→xám, BROKEN→đỏ mới đổi màu

## 📁 Danh sách file đã sửa

| File | Thay đổi |
|---|---|
| `app/Http/Controllers/Admin/SeatManageController.php` | Thêm method `assertSeatNotUsed()`, sửa `assertSeatRoomNotLockedForRealtime()`, thêm validate vào 6 method, sửa auto-select, bỏ zone restriction |
| `app/Http/Controllers/Admin/RoomManageController.php` | Thêm Cache import, sửa `seats()` xử lý showtime_id + dynamic status |
| `resources/views/admin/seats/index.blade.php` | Xoá `dyn-available`, xoá CSS, thêm comment |

