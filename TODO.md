# TODO: Tích hợp TabAuthMiddleware vào Routes

## Mục tiêu
Áp dụng middleware `tab.auth` (TabAuthMiddleware) vào tất cả các route đang dùng `auth` để hỗ trợ đăng nhập đa tab độc lập.

## Các bước thực hiện

### Step 1: Sửa TabAuthMiddleware
- [x] Cho phép pass-through nếu request đã có session auth (Auth::check())
- [ ] File: `app/Http/Middleware/TabAuthMiddleware.php`

### Step 2: Thêm route `login.tab` 
- [ ] Thêm route GET `/login/tab` trong `routes/web.php`
- [ ] Route trỏ về view `auth.login` (dùng chung)
- [ ] File: `routes/web.php`

### Step 3: Áp dụng `tab.auth` vào các route groups
- [ ] Route group `auth` (coin, reviews, profile, tickets, booking)
- [ ] Route group `auth, admin` (admin dashboard, film management, rooms, showtimes, accounts, combos, products, vouchers, promotions, banners, bookings, seats)
- [ ] Route group `auth, staff.permission:xxx` (staff dashboard, booking-lookup, check-in, issue-support, sell-tickets)
- [ ] File: `routes/web.php`

### Step 4: Kiểm tra
- [ ] Xác nhận không có route nào bị thiếu hoặc sai

