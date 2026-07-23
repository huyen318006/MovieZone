# TODO: Hiển thị trạng thái động cho sơ đồ ghế admin

## ✅ HOÀN THÀNH

- [x] **Controller** (`app/Http/Controllers/Admin/SeatManageController.php`): Sửa method `index()` để hỗ trợ query param `showtime_id`, load dynamic status cho từng ghế (AVAILABLE/SOLD/HELD/LOCKED/BROKEN) dựa trên dữ liệu từ showtime_seats, booking_seats và cache. Gán `$seat->dynamic_status` cho từng ghế.
- [x] **View** (`resources/views/admin/seats/index.blade.php`):
  - Thêm dropdown chọn suất chiếu ở panel info (dòng `Suất chiếu`)
  - Hiển thị thông tin suất chiếu đang chọn trong legend
  - Mỗi ghế sẽ **đổi màu nền** theo trạng thái động giống bên customer:
    - 🟢 **Giữ nguyên màu loại ghế** (STANDARD/VIP/COUPLE) = AVAILABLE
    - 🔴 **Nền đỏ** (gradient #fecaca → #ef4444) = SOLD
    - 🟠 **Nền cam** (gradient #fde68a → #f59e0b) = HELD
    - ⚫ **Nền xám đậm** (gradient #94a3b8 → #475569) = LOCKED/BLOCKED
    - 🔴 **Nền đỏ** (gradient #fecaca → #ef4444) = BROKEN
  - Title tooltip hiển thị thêm trạng thái động
  - Legend thêm dòng hiển thị suất chiếu đang chọn
  - CSS đầy đủ cho các trạng thái
   - Fallback về trạng thái tĩnh nếu không chọn suất chiếu
- [x] **Fix bug**: "-- Trạng thái tĩnh --" không bị nhảy về suất gần nhất (dùng `$hasShowtimeParam` để phân biệt lần đầu vào trang vs cố tình chọn rỗng)

## Cách hoạt động

1. Admin vào trang Quản lý ghế (admin/seats?room_id=X)
2. Chọn 1 phòng bất kỳ
3. Xuất hiện dropdown "Suất chiếu" ở panel info, liệt kê các suất chiếu sắp tới
4. Mặc định chọn suất gần nhất → hiển thị trạng thái động cho từng ghế
5. Có thể chọn "-- Trạng thái tĩnh --" để về chế độ xem cũ
6. Nếu phòng chưa có suất chiếu → chỉ hiển thị trạng thái tĩnh

