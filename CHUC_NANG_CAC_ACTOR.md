# DANH SÁCH CHỨC NĂNG THEO ACTOR — HỆ THỐNG MOVIEZONE

> Dùng để vẽ sơ đồ Use Case. Mỗi chức năng = 1 Use Case.

---

## 👤 KHÁCH HÀNG (Customer) — 23 chức năng

### 1. Quản lý tài khoản (6 UC)
| # | Mã | Tên chức năng | Mô tả ngắn |
|---|-----|---------------|------------|
| 1 | UC-01 | Đăng ký tài khoản | Tạo tài khoản mới (email + mật khẩu) |
| 2 | UC-02 | Đăng nhập | Đăng nhập bằng Email hoặc Google OAuth |
| 3 | UC-03 | Đăng xuất | Thoát phiên đăng nhập |
| 4 | UC-04 | Quên mật khẩu | Gửi email đặt lại mật khẩu |
| 5 | UC-05 | Đặt lại mật khẩu | Tạo mật khẩu mới từ link trong email |
| 6 | UC-06 | Xem/Sửa hồ sơ + Đổi mật khẩu | Cập nhật thông tin cá nhân, đổi MK |

### 2. Phim & Lịch chiếu (4 UC)
| # | Mã | Tên chức năng | Mô tả ngắn |
|---|-----|---------------|------------|
| 7 | UC-07 | Xem danh sách phim | Lọc theo thể loại, trạng thái (NOW_SHOWING/COMING_SOON) |
| 8 | UC-08 | Xem chi tiết phim | Thông tin phim, trailer, đánh giá |
| 9 | UC-09 | Xem lịch chiếu | Chọn ngày, xem suất chiếu theo rạp/phòng |
| 10 | UC-10 | Chọn suất chiếu | Chọn suất chiếu để bắt đầu đặt vé |

### 3. Đặt vé (6 UC) — Luồng include
| # | Mã | Tên chức năng | Mô tả ngắn |
|---|-----|---------------|------------|
| 11 | UC-11 | Chatbot hỗ trợ | Hỏi đáp bằng menu + Gemini AI |
| 12 | UC-12 | Chọn ghế | Xem sơ đồ ghế, chọn ghế (giữ 5 phút theo thời gian thực) |
| 13 | UC-13 | Chọn Combo | Chọn combo bắp nước, đồ ăn lẻ |
| 14 | UC-14 | Áp Voucher | Nhập mã giảm giá |
| 15 | UC-15 | Xác nhận đặt vé | Xem lại thông tin, chọn thanh toán |
| 16 | UC-16 | Thanh toán QR Online | Quét mã QR qua ngân hàng (Sepay) |

### 4. Vé & Đánh giá (4 UC)
| # | Mã | Tên chức năng | Mô tả ngắn |
|---|-----|---------------|------------|
| 17 | UC-17 | Xem vé đã mua | Danh sách vé đã đặt, chi tiết vé |
| 18 | UC-18 | Đánh giá phim | Gửi rating (sao) + bình luận |
| 19 | UC-19 | Sửa/Xóa đánh giá | Chỉnh sửa hoặc xoá review của mình |
| 20 | UC-20 | Xem số dư Coin | Xem điểm thưởng tích luỹ |

### 5. Điểm thưởng & Tiện ích (3 UC)
| # | Mã | Tên chức năng | Mô tả ngắn |
|---|-----|---------------|------------|
| 21 | UC-21 | Điểm danh hàng ngày | Daily check-in (streak) để nhận Coin |
| 22 | UC-22 | Xem khuyến mãi | Danh sách chương trình khuyến mãi |
| 23 | UC-23 | Xem tin tức | Xem bài viết, tin tức điện ảnh |

---

## 🧑‍💼 NHÂN VIÊN (Staff) — 5 chức năng

| # | Mã | Tên chức năng | Mô tả ngắn | Controller/Service chính |
|---|-----|---------------|------------|--------------------------|
| 1 | UC-S01 | Dashboard Staff | Xem thống kê trong ca: check-in, booking mới, thanh toán tiền mặt | StaffDashboardController → StaffDashboardService |
| 2 | UC-S02 | Check-in vé QR | Quét QR (camera), nhập mã thủ công, check-in hàng loạt (batch), in hoá đơn PDF | CheckInController → CheckInService |
| 3 | UC-S03 | Tra cứu Booking/Vé | Tra theo mã booking, ticket code, email, SĐT; xem audit logs | BookingLookupController → BookingLookupService |
| 4 | UC-S04 | Hỗ trợ sự cố đặt vé | Chẩn đoán QR lỗi, booking lỗi, thanh toán thất bại; đề xuất hướng xử lý | StaffIssueSupportController → IssueSupportService |
| 5 | UC-S05 | Bán vé tại quầy | Chọn phim → suất chiếu → ghế → combo → nhập thông tin KH → thanh toán (Online/Tiền mặt) → in hoá đơn | BookTicketsController → StaffBookingService, SepayService, TicketService |

---

## 🛡️ QUẢN TRỊ VIÊN (Admin) — 12 chức năng CHÍNH

| # | Mã | Tên chức năng | Mô tả ngắn | Controller/Service chính |
|---|-----|---------------|------------|--------------------------|
| 1 | UC-A01 | Dashboard Admin | Thống kê tổng quan doanh thu, booking, phim, người dùng | AdminDashboardController → AdminDashboardService |
| 2 | UC-A02 | Quản lý Phim | CRUD phim + Ngừng chiếu/Khôi phục + Gửi email thông báo khi hủy | FilmManageController |
| 3 | UC-A03 | Quản lý Phòng chiếu | CRUD phòng chiếu + Ẩn/Khôi phục | RoomManageController |
| 4 | UC-A04 | Quản lý Ghế | CRUD ghế + Khoá/Mở + Thêm Batch + Đổi loại ghế (Standard/VIP/Couple) | SeatManageController |
| 5 | UC-A05 | Quản lý Suất chiếu | Wizard tạo suất chiếu 3 bước + Kiểm tra trùng lịch + Hủy suất chiếu | ShowtimeManageController |
| 6 | UC-A06 | Quản lý Booking | Xem danh sách booking + Chi tiết + Hủy/Giải phóng ghế + Check-in hỗ trợ | BookingManageController |
| 7 | UC-A07 | Quản lý Tài khoản | CRUD người dùng + Khoá/Mở tài khoản + Phân quyền (Admin/Staff/Customer) | AccountManageController |
| 8 | UC-A08 | Quản lý Sản phẩm (đồ ăn lẻ) | CRUD sản phẩm (tên, giá, mô tả) | ProductManageController |
| 9 | UC-A09 | Quản lý Combo | CRUD combo (gói bắp nước, kèm sản phẩm) | ComboManageController |
| 10 | UC-A10 | Quản lý Voucher | CRUD mã giảm giá (code, % giảm, hạn dùng) | VoucherManageController |
| 11 | UC-A11 | Quản lý Khuyến mãi | CRUD chương trình khuyến mãi | PromotionManageController |
| 12 | UC-A12 | Quản lý Banner | CRUD banner trang chủ | BannerManageController |

---

## 📊 TỔNG KẾT

| Actor | Số chức năng |
|-------|:------------:|
| 👤 Khách hàng (Customer) | **23** |
| 🧑‍💼 Nhân viên (Staff) | **5** |
| 🛡️ Quản trị viên (Admin) | **12** |
| **Tổng cộng** | **~40** |

---

## 🔗 QUAN HỆ INCLUDE (Luồng đặt vé)

```
[UC-10 Chọn suất chiếu] ──«include»──> [UC-12 Chọn ghế]
[UC-12 Chọn ghế]       ──«include»──> [UC-13 Chọn Combo]
[UC-13 Chọn Combo]     ──«include»──> [UC-14 Áp Voucher]
[UC-14 Áp Voucher]     ──«include»──> [UC-15 Xác nhận đặt vé]
[UC-15 Xác nhận]       ──«include»──> [UC-16 Thanh toán QR]
```

**Bán vé tại quầy (Staff) dùng chung luồng include:**
```
[UC-S05 Bán vé quầy] ──«include»──> [UC-12 Chọn ghế]
[UC-S05 Bán vé quầy] ──«include»──> [UC-13 Chọn Combo]
[UC-S05 Bán vé quầy] ──«include»──> [UC-15 Xác nhận đặt vé]
[UC-S05 Bán vé quầy] ──«include»──> [UC-16 Thanh toán QR]
```

---

## 🔗 KẾT NỐI VỚI HỆ THỐNG NGOÀI

| Hệ thống | Use Case liên quan |
|----------|-------------------|
| ☁️ Google OAuth | UC-02 Đăng nhập |
| 🏦 Sepay (Payment Gateway) | UC-16 Thanh toán QR Online |
| 📧 Email Service | UC-04 Quên mật khẩu, UC-A05 Hủy suất chiếu (gửi email) |
| 🤖 Gemini AI | UC-11 Chatbot hỗ trợ |

---

*Tài liệu này được tạo từ mã nguồn thực tế của hệ thống MovieZone. Mỗi chức năng đều có Controller, Service, View và Route tương ứng.*
