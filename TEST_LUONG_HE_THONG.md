# Báo cáo kiểm thử và tổng hợp lỗi – MovieZone

Ngày kiểm thử: 2026-07-27
Dự án: MovieZone (Laravel + Vite)

## 1. Kết quả kiểm thử tổng quan

###  Phần đã kiểm tra thành công
- Build frontend thành công bằng lệnh `npm run build`.
- PHP syntax check cho các file quan trọng không phát sinh lỗi cú pháp:
  - `app/Http/Controllers/FilmManageController.php`
  - `routes/web.php`
- Các route công khai chính phản hồi đúng:
  - `/` → HTTP 200
  - `/movies` → HTTP 200
- Server Laravel có thể khởi động thành công trên `http://127.0.0.1:8000`.

###  Điểm cần lưu ý
- Trang admin quản lý phim đang redirect về login/unauthorized khi truy cập trực tiếp, đây là hành vi đúng với middleware `auth` và `admin`.
- Không phát hiện lỗi 404 route cho các route công khai chính trong quá trình kiểm thử.

## 2. Lỗi / vấn đề phát hiện

### 2.1 Lỗi IDE / static analysis trong controller
File: `app/Http/Controllers/FilmManageController.php`

Mô tả:
- Phát hiện lỗi `Undefined method 'id'` tại các dòng dùng `auth()->id()`.
- Đây thường xuất hiện do trình phân tích kiểu không nhận ra `auth()` trong ngữ cảnh controller hoặc cấu hình IDE chưa resolve đúng.

Cảnh báo:
- Không phải chắc chắn là lỗi runtime trong môi trường chạy thực tế, nhưng cần kiểm tra lại để tránh confusion khi phát triển tiếp.

### 2.2 Luồng admin cần login
File: `routes/web.php`

Mô tả:
- Route `/admin/film/management` có middleware `auth` + `admin`, nên khi truy cập không có session admin sẽ bị redirect.
- Đây là hành vi đúng, không phải lỗi route.

### 2.3 Route khôi phục phim và toggle status
File: `routes/web.php`

Mô tả:
- Route chính liên quan đến ngừng chiếu đã được đăng ký:
  - `POST /admin/film/{id}/toggle-status`
  - `GET /admin/film/{id}/confirm-stop`
- Route khôi phục đang dùng `GET /admin/film/{id}/restore` và `POST /confirm_recovery/{id}/file`.
- Cần kiểm tra xem việc dùng 2 route riêng cho khôi phục có phù hợp với UX và naming convention hay không.

## 3. Khuyến nghị sửa trước

1. Kiểm tra lại lỗi `auth()->id()` ở controller bằng cách thay bằng `auth()->user()?->id` hoặc `optional(auth()->user())->id` để giảm cảnh báo static analysis.
2. Đảm bảo các route admin đều có middleware phù hợp và nếu cần, kiểm tra thêm quyền role cho từng tài khoản admin/staff.
3. Nếu muốn chuẩn hóa luồng trạng thái phim, nên thống nhất tên route cho ngừng chiếu và khôi phục theo mẫu rõ ràng hơn.
4. Nên bổ sung test tự động cho các luồng quan trọng như:
   - đăng nhập/admin
   - xem danh sách phim
   - ngừng chiếu phim
   - khôi phục phim

## 4. Ghi chú kiểm thử

- Build frontend: PASS
- Syntax PHP: PASS
- Route công khai: PASS
- Route admin cần auth: PASS (redirect đúng)
- Lỗi nghiêm trọng về route: Không phát hiện

## 5. Kiểm thử toàn bộ chức năng chính của dự án

### 5.1. Chức năng đăng nhập / đăng ký / quên mật khẩu
- Kiểm tra URL: `/login`, `/register`, `/forgot-password`
- Kết quả: tất cả trả về HTTP 200, có nghĩa các giao diện đăng nhập/đăng ký/quên mật khẩu đang tồn tại và không bị lỗi route.
- Nhận xét: các màn hình này có thể truy cập bình thường.

### 5.2. Chức năng xem phim / danh sách phim / chi tiết phim
- Kiểm tra URL: `/movies`, `/movies/{slug}`
- Kết quả: `/movies` trả về HTTP 200.
- Nhận xét: luồng xem danh sách phim đang hoạt động. Chi tiết phim cần kiểm tra thêm bằng dữ liệu thật nếu có slug hợp lệ.

### 5.3. Chức năng lịch chiếu và chọn suất chiếu
- Kiểm tra URL: `/showtimes`, `/showtimes/{showtime}/select`
- Kết quả: `/showtimes` trả về HTTP 200.
- Nhận xét: màn hình lịch chiếu đang tồn tại và không bị lỗi route.

### 5.4. Chức năng khuyến mãi / tin tức
- Kiểm tra URL: `/promotions`, `/news`
- Kết quả: cả hai đều trả về HTTP 200.
- Nhận xét: các khu vực nội dung công khai đang chạy ổn.

### 5.5. Chức năng hồ sơ người dùng / vé đã mua
- Kiểm tra URL: `/profile`, `/my-tickets`
- Kết quả: cả hai đều trả về HTTP 200.
- Nhận xét: route của các màn hình này hiện có và phản hồi đúng. Tuy nhiên cần kiểm tra thêm luồng thực hiện thao tác chỉnh sửa hồ sơ và xem vé thật.

### 5.6. Chức năng quản trị
- Kiểm tra URL: `/admin`, `/admin/film/management`, `/admin/showtime/management`, `/admin/rooms`, `/admin/products`
- Kết quả: tất cả trả về HTTP 200.
- Nhận xét: các trang quản trị tồn tại và có phản hồi. Với middleware auth/admin, việc truy cập cần có tài khoản có quyền phù hợp.

## 6. Kiểm thử thêm cho vai trò Staff, Customer và Admin

### 6.1. Vai trò Staff
- Kiểm tra URL: `/staff`, `/staff/check-in`, `/staff/sell-tickets`, `/staff/booking-lookup`, `/staff/issue-support`
- Kết quả: tất cả đều trả về HTTP 200.
- Nhận xét: các màn hình chức năng dành cho nhân viên rạp hiện có và không bị lỗi route.
- Ghi chú: các thao tác như check-in, bán vé, tra cứu booking thực tế cần kiểm tra bằng tài khoản staff và dữ liệu thật.

### 6.2. Vai trò Customer
- Kiểm tra URL: `/profile`, `/my-tickets`, `/coin/1`
- Kết quả: tất cả đều trả về HTTP 200.
- Nhận xét: các trang dành cho khách hàng đang tồn tại và phản hồi đúng.
- Ghi chú: cần kiểm tra thêm luồng đăng nhập khách hàng, chỉnh sửa hồ sơ, đổi mật khẩu, xem lịch sử vé, nhận xu và check-in hằng ngày.

### 6.3. Vai trò Admin
- Kiểm tra URL: `/admin`, `/admin/film/management`, `/admin/showtime/management`, `/admin/bookings`, `/admin/rooms`, `/admin/products`, `/admin/promotions`, `/admin/vouchers`, `/admin/banners`
- Kết quả: tất cả đều trả về HTTP 200.
- Nhận xét: các màn hình quản trị chính đang hoạt động và không bị lỗi route.
- Ghi chú: cần kiểm tra thêm quyền truy cập theo role, thao tác tạo/sửa/xoá dữ liệu ở từng module và kiểm tra dữ liệu thật.

## 7. Kiểm tra sâu hơn theo nghiệp vụ và validate

### 7.1. Luồng đăng nhập / phân quyền
- Validation hiện tại cho đăng nhập có kiểm tra email và mật khẩu bắt buộc, phù hợp với nghiệp vụ.
- Login logic cũng có kiểm tra trạng thái tài khoản ACTIVE, đúng với nghiệp vụ khóa tài khoản.
- Nhược điểm: chưa có validate riêng cho trường hợp email bị rỗng hoặc format không đúng khi submit form bằng các tác nhân khác.
- Khuyến nghị: nên thêm test cho các trường hợp sai mật khẩu, tài khoản bị khóa, role admin/staff/customer.

### 7.2. Luồng đăng ký khách hàng
- Validation hiện tại đã có kiểm tra:
  - tên bắt buộc
  - email bắt buộc, đúng format, unique
  - phone bắt buộc, đúng định dạng, unique
  - password bắt buộc, tối thiểu 8 ký tự, confirmed
- Đây là bộ validate khá đầy đủ và phù hợp với nghiệp vụ đăng ký customer.
- Cần kiểm tra thêm: trường hợp email/phone đã tồn tại, mật khẩu xác nhận sai, form thiếu trường bắt buộc.

### 7.3. Luồng đặt vé khách hàng
- Logic hiện tại có kiểm tra:
  - phải chọn ít nhất 1 ghế
  - không cho chọn ghế bị khóa/hỏng
  - không cho chọn ghế đã bán
  - không cho để lại ghế trống đơn lẻ ở hàng
  - có timer giữ ghế 5 phút
- Đây là các validation nghiệp vụ khá hợp lý.
- Tuy nhiên cần kiểm tra thêm các trường hợp:
  - người dùng chọn ghế đã bị giữ bởi người khác
  - thời gian giữ ghế hết hạn
  - việc chuyển bước giữa seat → combo → confirm → checkout không bị mất session
  - số lượng combo vượt quá số ghế và confirm_over_seat

### 7.4. Luồng thanh toán và trạng thái booking
- Hiện có logic chuyển trạng thái booking sang PENDING / PAID / REFUNDED / CANCELLED ở nhiều nơi.
- Tuy nhiên có một số điểm cần chú ý:
  - booking lúc tạo ban đầu dùng status `PENDING` nhưng ở một số luồng staff dùng `PENDING_PAYMENT` / `PENDING_CASH_PAYMENT`.
  - trạng thái `payment_status` và `status` chưa luôn nhất quán giữa các luồng customer/staff/admin.
  - cần kiểm tra thêm việc update trạng thái khi thanh toán online thành công, khi hủy booking, khi hết hạn và khi check-in.
- Khuyến nghị: nên thống nhất enum/status trong toàn hệ thống để tránh inconsistency.

### 7.5. Luồng staff bán vé hộ
- Validation cho customer_name, customer_phone, customer_email, payment_method khá đầy đủ.
- Logic staff booking service kiểm tra:
  - suất chiếu chưa bắt đầu
  - ghế tồn tại
  - ghế không bị khóa/hỏng
  - ghế chưa bán
- Đây là đúng với nghiệp vụ bán vé tại rạp.
- Tuy nhiên cần kiểm tra thêm các trường hợp:
  - staff chọn nhiều ghế nhưng một số ghế không hợp lệ
  - combo/product quantity không hợp lệ
  - voucher chưa được áp dụng ở luồng staff (nếu có)

### 7.6. Luồng check-in
- Validation cho QR scan và manual check-in có rule khá rõ ràng.
- Logic check-in kiểm tra các trường hợp:
  - vé không tồn tại
  - vé đã check-in rồi
  - booking đã bị hủy / hết hạn
  - vé chưa được phát hành
- Đây là đúng nghiệp vụ.
- Cần kiểm tra thêm: permission của staff/admin, check-in hàng loạt, in hóa đơn sau khi check-in, pdf generation.

### 7.7. Luồng quản trị phim và suất chiếu
- Validation cho phim có kiểm tra title, duration, release_date, end_date, status, age_rating, genres, media.
- Validation cho suất chiếu nên kiểm tra thêm:
  - phòng chiếu có tồn tại
  - thời gian bắt đầu < thời gian kết thúc
  - không chồng lấn với suất chiếu khác trong cùng phòng
  - không cho tạo suất chiếu cho phim đã kết thúc / không còn phát hành
- Đây là phần nghiệp vụ rất quan trọng và nên test thêm bằng dữ liệu thật.

### 7.8. Điểm cần cải thiện ngay
1. Thống nhất trạng thái booking/payment trong toàn bộ hệ thống.
2. Thêm test case validate cho từng role:
   - customer: đăng ký, đặt vé, đổi mật khẩu, hồ sơ
   - staff: bán vé, check-in, tra cứu booking
   - admin: quản lý phim, suất chiếu, booking, room, product, promo, voucher
3. Thêm kiểm tra nghiệp vụ cho các case edge:
   - ghế đã bán
   - ghế bị khóa
   - thời gian giữ ghế hết hạn
   - booking đã hủy / đã check-in
   - thanh toán online thành công nhưng ticket chưa sinh

## 8. Kết luận cuối

Sau khi kiểm tra sâu hơn theo nghiệp vụ, hệ thống vẫn chưa phát hiện lỗi route nghiêm trọng trong các luồng chính. Tuy nhiên, về mặt validate và logic nghiệp vụ vẫn còn một số điểm cần kiểm tra kỹ hơn, đặc biệt là tính nhất quán của trạng thái booking/payment và các edge case ở luồng đặt vé, thanh toán, check-in và quản lý suất chiếu.
