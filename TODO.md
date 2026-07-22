## Tasks
- [x] Bóc tách yêu cầu: sửa sell-tickets.blade.php theo flow bán vé staff (hiển thị đúng route staff.sell-seat)
- [x] Kiểm tra các điểm hiện đang bị sai/thiếu liên quan đến sell-tickets.blade.php (routes/BookTicketsController)
- [x] Đọc sell-tickets.blade.php hiện tại + đối chiếu session/flow (sell-seat -> submitseat -> sell-tickets-combo)
- [x] Lập plan chỉnh sửa chi tiết theo từng file
- [x] Chờ duyệt plan từ user trước khi sửa code
- [x] Tạo TODO.md updates theo từng bước sau khi hoàn thành

## NEW FEATURE: Bulk update seat type by row
- [x] Thêm method `bulkUpdateType()` trong `SeatManageController.php`
- [x] Thêm route `POST /admin/seats/bulk-update-type` trong `routes/web.php`
- [x] Thêm UI (button + modal) trong `resources/views/admin/seats/index.blade.php`

