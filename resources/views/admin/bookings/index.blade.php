@extends('layout.admin')

@section('title', 'Quản lý đặt vé')

@section('content')
    {{-- Alert Messages --}}
    <div id="alert-container"></div>

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1">Quản lý đặt vé</h3>
            <p class="text-muted mb-0">Tra cứu, xem chi tiết, hỗ trợ check-in và hủy đơn đặt vé của khách hàng.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" id="btn-refresh" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Làm mới
            </button>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Tổng số booking</div>
                    <div class="fs-3 fw-bold" id="stat-total-bookings">—</div>
                    <div class="text-muted small">Tất cả trạng thái</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Đã thanh toán (PAID)</div>
                    <div class="fs-3 fw-bold text-success" id="stat-paid-bookings">—</div>
                    <div class="text-muted small">Vé hợp lệ check-in</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Chờ thanh toán (PENDING)</div>
                    <div class="fs-3 fw-bold text-warning" id="stat-pending-bookings">—</div>
                    <div class="text-muted small">Chờ xử lý thanh toán</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Đã hủy (CANCELLED)</div>
                    <div class="fs-3 fw-bold text-danger" id="stat-cancelled-bookings">—</div>
                    <div class="text-muted small">Ghế đã được giải phóng</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Đã hết hạn (EXPIRED)</div>
                    <div class="fs-3 fw-bold text-muted" id="stat-expired-bookings">—</div>
                    <div class="text-muted small">Hết hạn thanh toán</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Bộ lọc đặt vé</div>
                <small class="text-muted">Lọc theo mã booking, thông tin khách hàng, trạng thái và ngày đặt.</small>
            </div>
        </div>

        <div class="card-body">
            <form id="filter-form" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Mã Booking</label>
                    <input type="text" name="booking_code" id="filter-booking-code" class="form-control" placeholder="Mã BK...">
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Tên khách hàng</label>
                    <input type="text" name="customer_name" id="filter-customer-name" class="form-control" placeholder="Tên khách...">
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="filter-email" class="form-control" placeholder="Email...">
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" id="filter-phone" class="form-control" placeholder="SĐT...">
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" id="filter-status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="PENDING">PENDING (Chờ thanh toán)</option>
                        <option value="PAID">PAID (Đã thanh toán)</option>
                        <option value="CANCELLED">CANCELLED (Đã hủy)</option>
                        <option value="EXPIRED">EXPIRED (Hết hạn)</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Ngày đặt</label>
                    <input type="date" name="booking_date" id="filter-booking-date" class="form-control">
                </div>

                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-funnel me-1"></i>
                        Lọc danh sách
                    </button>
                    <button type="button" id="btn-reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>
                        Xóa bộ lọc
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bookings Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Danh sách đặt vé</div>
                <small class="text-muted">Các thao tác thay đổi đều được ghi log hệ thống.</small>
            </div>
            <div class="text-muted small" id="table-record-count">
                Hiển thị 0/0 bản ghi
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="bookings-table" style="min-width: 1100px;">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Mã Booking</th>
                            <th>Khách hàng</th>
                            <th>Phim / Suất chiếu</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                            <th>Ngày đặt</th>
                            <th style="width: 140px;" class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-tbody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Đang tải dữ liệu booking...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3" id="pagination-container">
                <!-- Pagination elements will be injected here -->
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="bi bi-ticket-detailed me-2 text-primary"></i> Chi tiết Booking: <span id="detail-booking-code" class="fw-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Tabs --}}
                    <ul class="nav nav-tabs nav-fill border-bottom" id="bookingTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-3 border-0" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab" aria-controls="info-tab-pane" aria-selected="true">
                                <i class="bi bi-info-circle me-2"></i> Thông tin chi tiết
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 border-0" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment-tab-pane" type="button" role="tab" aria-controls="payment-tab-pane" aria-selected="false">
                                <i class="bi bi-credit-card me-2"></i> Lịch sử Payment
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 border-0" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-tab-pane" type="button" role="tab" aria-controls="history-tab-pane" aria-selected="false">
                                <i class="bi bi-clock-history me-2"></i> Lịch sử thay đổi
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content p-4" id="bookingTabContent">
                        {{-- Tab 1: Info --}}
                        <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" aria-labelledby="info-tab" tabindex="0">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-uppercase text-muted mb-3 small">Thông tin khách hàng</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 120px;">Họ tên:</td>
                                            <td id="detail-customer-name" class="fw-semibold"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Số điện thoại:</td>
                                            <td id="detail-customer-phone"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email:</td>
                                            <td id="detail-customer-email"></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-uppercase text-muted mb-3 small">Thông tin suất chiếu</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 120px;">Phim:</td>
                                            <td id="detail-movie-title" class="fw-semibold text-primary"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Rạp:</td>
                                            <td id="detail-cinema-name"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Phòng / Suất:</td>
                                            <td><span id="detail-room-name"></span> • <span id="detail-showtime-time" class="fw-semibold text-danger"></span></td>
                                        </tr>
                                    </table>
                                </div>

                                <hr class="my-2 text-muted opacity-25">

                                <div class="col-md-12">
                                    <h6 class="fw-bold text-uppercase text-muted mb-3 small">Chi tiết Vé & Sản phẩm đi kèm</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Loại</th>
                                                    <th>Nội dung chi tiết</th>
                                                    <th class="text-end">Đơn giá</th>
                                                    <th class="text-center" style="width: 80px;">SL</th>
                                                    <th class="text-end" style="width: 150px;">Tổng tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detail-items-tbody"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-md-6 offset-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted">Tiền vé:</td>
                                            <td class="text-end" id="summary-ticket-amount"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tiền Combo:</td>
                                            <td class="text-end" id="summary-combo-amount"></td>
                                        </tr>
                                        <tr id="summary-discount-row">
                                            <td class="text-muted">Giảm giá (<span id="summary-voucher-code"></span>):</td>
                                            <td class="text-end text-success" id="summary-discount-amount"></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold fs-5">Tổng tiền:</td>
                                            <td class="text-end fw-bold fs-5 text-danger" id="summary-final-amount"></td>
                                        </tr>
                                    </table>
                                </div>

                                <hr class="my-2 text-muted opacity-25">

                                <div class="col-md-12">
                                    <h6 class="fw-bold text-uppercase text-muted mb-3 small">Trạng thái QR Ticket & Check-in</h6>
                                    <div class="row g-3" id="detail-tickets-container"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 2: Payments --}}
                        <div class="tab-pane fade" id="payment-tab-pane" role="tabpanel" aria-labelledby="payment-tab" tabindex="0">
                            <h6 class="fw-bold text-uppercase text-muted mb-3 small">Lịch sử giao dịch liên quan</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Phương thức</th>
                                            <th>Mã giao dịch</th>
                                            <th class="text-end">Số tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thời gian</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detail-payments-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab 3: History --}}
                        <div class="tab-pane fade" id="history-tab-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
                            <h6 class="fw-bold text-uppercase text-muted mb-3 small">Nhật ký thay đổi trạng thái</h6>
                            <div class="position-relative ps-4 border-start ms-2" id="detail-history-timeline"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" id="detail-modal-footer"></div>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="cancel-booking-form">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="cancelModalLabel">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Hủy Đơn Đặt Vé
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="cancel-booking-id">
                        <p>Bạn có chắc chắn muốn hủy đơn đặt vé <strong id="cancel-booking-code-text"></strong>? Hành động này không thể hoàn tác.</p>
                        
                        <div class="alert alert-warning py-2 small">
                            <i class="bi bi-info-circle-fill me-1"></i> Tất cả ghế đã chọn trong đơn sẽ được giải phóng trở lại trạng thái Trống.
                        </div>

                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label fw-semibold">Lý do hủy đơn <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" id="cancel_reason" rows="3" required placeholder="Nhập lý do hủy đơn (bắt buộc)..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-danger" id="btn-confirm-cancel">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="cancel-spinner"></span>
                            Xác nhận hủy
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: "";
            position: absolute;
            left: -29px;
            top: 4px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--bs-primary);
            border: 2px solid #fff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        }
        .timeline-item.cancelled::before {
            background-color: var(--bs-danger);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
        }
        .timeline-item.checkin::before {
            background-color: var(--bs-success);
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.25);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const tabToken = '{{ \App\Helpers\TabAuthHelper::gettoken() }}';
            let currentPage = 1;

            // Load bookings
            function loadBookings(page = 1) {
                currentPage = page;
                const tbody = document.getElementById('bookings-tbody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Đang tải dữ liệu...
                        </td>
                    </tr>
                `;

                const formData = new FormData(document.getElementById('filter-form'));
                const params = new URLSearchParams();
                for (const [key, val] of formData.entries()) {
                    if (val) params.append(key, val);
                }
                params.append('page', page);
                params.append('tab_token', tabToken);
                fetch(`/admin/api/bookings?${params.toString()}`)
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            renderTable(res.data);
                            renderPagination(res.pagination);
                            updateStats(res.stats, res.pagination);
                        } else {
                            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">Không thể tải dữ liệu.</td></tr>`;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">Lỗi kết nối máy chủ.</td></tr>`;
                    });
            }

            // Update stats cards
            function updateStats(stats, pagination) {
                if (stats) {
                    document.getElementById('stat-total-bookings').textContent = stats.total;
                    document.getElementById('stat-paid-bookings').textContent = stats.paid;
                    document.getElementById('stat-pending-bookings').textContent = stats.pending;
                    document.getElementById('stat-cancelled-bookings').textContent = stats.cancelled;
                    document.getElementById('stat-expired-bookings').textContent = stats.expired;
                }
                if (pagination) {
                    document.getElementById('table-record-count').textContent = `Hiển thị ${pagination.per_page * (pagination.current_page - 1) + 1}-${Math.min(pagination.per_page * pagination.current_page, pagination.total)}/${pagination.total} bản ghi`;
                }
            }

            // Format VND
            function formatVND(amount) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
            }

            // Format Date
            function formatDateTime(dateTimeStr) {
                if (!dateTimeStr) return 'N/A';
                const date = new Date(dateTimeStr);
                return date.toLocaleString('vi-VN');
            }

            // Render table rows
            function renderTable(bookings) {
                const tbody = document.getElementById('bookings-tbody');
                if (bookings.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-calendar2-x fs-1 d-block mb-2"></i>Chưa có đơn đặt vé nào phù hợp.</td></tr>`;
                    return;
                }

                tbody.innerHTML = bookings.map(b => {
                    const customerName = b.user ? b.user.name : 'Khách vãng lai';
                    const customerContact = b.user ? `<small class="text-muted d-block">${b.user.phone || ''} • ${b.user.email || ''}</small>` : '';
                    const movieTitle = b.showtime && b.showtime.movie ? b.showtime.movie.title : 'N/A';
                    const cinemaName = b.showtime && b.showtime.cinema ? b.showtime.cinema.name : 'N/A';
                    const showtimeTime = b.showtime ? formatDateTime(b.showtime.start_time) : 'N/A';

                    // Status Badges
                    let statusBadge = '';
                    switch (b.status) {
                        case 'PENDING':
                            statusBadge = '<span class="badge text-bg-warning text-dark">PENDING</span>';
                            break;
                        case 'PAID':
                            statusBadge = '<span class="badge text-bg-success">PAID</span>';
                            break;
                        case 'CANCELLED':
                            statusBadge = '<span class="badge text-bg-danger">CANCELLED</span>';
                            break;
                        case 'EXPIRED':
                            statusBadge = '<span class="badge text-bg-secondary">EXPIRED</span>';
                            break;
                        default:
                            statusBadge = `<span class="badge text-bg-dark">${b.status}</span>`;
                    }

                    let paymentBadge = '';
                    switch (b.payment_status) {
                        case 'UNPAID':
                            paymentBadge = '<span class="badge text-bg-light border border-warning text-warning">UNPAID</span>';
                            break;
                        case 'PAID':
                            paymentBadge = '<span class="badge text-bg-light border border-success text-success">PAID</span>';
                            break;
                        case 'FAILED':
                            paymentBadge = '<span class="badge text-bg-light border border-danger text-danger">FAILED</span>';
                            break;
                        case 'REFUNDED':
                            paymentBadge = '<span class="badge text-bg-light border border-info text-info">REFUNDED</span>';
                            break;
                        default:
                            paymentBadge = `<span class="badge text-bg-light border border-dark text-dark">${b.payment_status}</span>`;
                    }

                    return `
                        <tr>
                            <td><strong class="text-primary">${b.booking_code}</strong></td>
                            <td>
                                <div class="fw-semibold">${customerName}</div>
                                ${customerContact}
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate" style="max-width: 250px;" title="${movieTitle}">${movieTitle}</div>
                                <small class="text-muted d-block">${cinemaName} • ${showtimeTime}</small>
                            </td>
                            <td class="fw-bold text-danger">${formatVND(b.final_amount)}</td>
                            <td>${statusBadge}</td>
                            <td>${paymentBadge}</td>
                            <td><small>${formatDateTime(b.created_at)}</small></td>
                            <td class="text-end">
                                <button class="btn btn-outline-info btn-sm btn-view-detail" data-id="${b.id}">
                                    <i class="bi bi-eye me-1"></i> Chi tiết
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                // View Details
                document.querySelectorAll('.btn-view-detail').forEach(btn => {
                    btn.addEventListener('click', function() {
                        showDetail(this.getAttribute('data-id'));
                    });
                });
            }

            // Render Pagination
            function renderPagination(pag) {
                const container = document.getElementById('pagination-container');
                if (pag.total === 0) {
                    container.innerHTML = '';
                    return;
                }

                const from = (pag.current_page - 1) * pag.per_page + 1;
                const to = Math.min(pag.current_page * pag.per_page, pag.total);

                let pageButtons = '';
                const maxVisible = 5;
                let start = Math.max(1, pag.current_page - 2);
                let end = Math.min(pag.last_page, start + maxVisible - 1);
                if (end - start + 1 < maxVisible) {
                    start = Math.max(1, end - maxVisible + 1);
                }

                for (let i = start; i <= end; i++) {
                    pageButtons += `
                        <button class="btn btn-sm ${i === pag.current_page ? 'btn-primary' : 'btn-outline-secondary'} btn-page" data-page="${i}">
                            ${i}
                        </button>
                    `;
                }

                container.innerHTML = `
                    <div class="text-muted small">
                        Hiển thị <strong>${from}-${to}</strong> trong tổng số <strong>${pag.total}</strong> kết quả
                    </div>
                    <div class="btn-group gap-1">
                        <button class="btn btn-sm btn-outline-secondary btn-page" data-page="1" ${pag.current_page === 1 ? 'disabled' : ''}>
                            <i class="bi bi-chevron-double-left"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary btn-page" data-page="${pag.current_page - 1}" ${pag.current_page === 1 ? 'disabled' : ''}>
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        ${pageButtons}
                        <button class="btn btn-sm btn-outline-secondary btn-page" data-page="${pag.current_page + 1}" ${pag.current_page === pag.last_page ? 'disabled' : ''}>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary btn-page" data-page="${pag.last_page}" ${pag.current_page === pag.last_page ? 'disabled' : ''}>
                            <i class="bi bi-chevron-double-right"></i>
                        </button>
                    </div>
                `;

                container.querySelectorAll('.btn-page').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const page = parseInt(this.getAttribute('data-page'));
                        if (page) loadBookings(page);
                    });
                });
            }

            // Show detail modal
            function showDetail(id) {
                fetch(`/admin/bookings/${id}?tab_token=${tabToken}`)
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            const b = res.booking;
                            
                            document.getElementById('detail-booking-code').textContent = b.booking_code;
                            document.getElementById('detail-customer-name').textContent = b.customer_name || (b.user ? b.user.name : 'Khách vãng lai');
                            document.getElementById('detail-customer-phone').textContent = b.customer_phone || (b.user ? b.user.phone : 'N/A');
                            document.getElementById('detail-customer-email').textContent = b.customer_email || (b.user ? b.user.email : 'N/A');

                            document.getElementById('detail-movie-title').textContent = b.showtime && b.showtime.movie ? b.showtime.movie.title : 'N/A';
                            document.getElementById('detail-cinema-name').textContent = b.showtime && b.showtime.cinema ? b.showtime.cinema.name : 'N/A';
                            document.getElementById('detail-room-name').textContent = b.showtime && b.showtime.room ? b.showtime.room.name : 'N/A';
                            document.getElementById('detail-showtime-time').textContent = b.showtime ? formatDateTime(b.showtime.start_time) : 'N/A';

                            // Items
                            const itemsTbody = document.getElementById('detail-items-tbody');
                            itemsTbody.innerHTML = '';

                            if (b.booking_seats && b.booking_seats.length > 0) {
                                b.booking_seats.forEach(s => {
                                    itemsTbody.innerHTML += `
                                        <tr>
                                            <td><span class="badge bg-primary">VÉ GHẾ</span></td>
                                            <td>Ghế <strong>${s.seat_code}</strong> (${s.seat_type})</td>
                                            <td class="text-end">${formatVND(s.price)}</td>
                                            <td class="text-center">1</td>
                                            <td class="text-end fw-bold">${formatVND(s.price)}</td>
                                        </tr>
                                    `;
                                });
                            }

                            if (b.booking_combos && b.booking_combos.length > 0) {
                                b.booking_combos.forEach(c => {
                                    itemsTbody.innerHTML += `
                                        <tr>
                                            <td><span class="badge bg-success">COMBO</span></td>
                                            <td><strong>${c.combo ? c.combo.name : 'Combo'}</strong><br><small class="text-muted">${c.combo ? c.combo.description : ''}</small></td>
                                            <td class="text-end">${formatVND(c.unit_price)}</td>
                                            <td class="text-center">${c.quantity}</td>
                                            <td class="text-end fw-bold">${formatVND(c.total_price)}</td>
                                        </tr>
                                    `;
                                });
                            }

                            document.getElementById('summary-ticket-amount').textContent = formatVND(b.total_ticket_amount);
                            document.getElementById('summary-combo-amount').textContent = formatVND(b.total_combo_amount);
                            
                            const discountRow = document.getElementById('summary-discount-row');
                            if (b.discount_amount > 0) {
                                discountRow.style.display = 'table-row';
                                document.getElementById('summary-discount-amount').textContent = '-' + formatVND(b.discount_amount);
                                const voucherCode = res.vouchers && res.vouchers.length > 0 ? res.vouchers[0].code : 'VOUCHER';
                                document.getElementById('summary-voucher-code').textContent = voucherCode;
                            } else {
                                discountRow.style.display = 'none';
                            }
                            document.getElementById('summary-final-amount').textContent = formatVND(b.final_amount);

                            // Tickets
                            const ticketsContainer = document.getElementById('detail-tickets-container');
                            ticketsContainer.innerHTML = '';
                            
                            if (b.tickets && b.tickets.length > 0) {
                                b.tickets.forEach(t => {
                                    let ticketStatusBadge = '';
                                    let checkinBtn = '';

                                    if (t.status === 'USED') {
                                        const checkedBy = t.checked_in_by_user ? t.checked_in_by_user.name : 'N/A';
                                        ticketStatusBadge = `<span class="badge bg-success">CHECKED-IN</span>`;
                                        checkinBtn = `
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-check2-circle text-success me-1"></i> Check-in lúc:<br>
                                                <strong>${formatDateTime(t.checked_in_at)}</strong> bởi ${checkedBy}
                                            </div>
                                        `;
                                    } else if (t.status === 'CANCELLED') {
                                        ticketStatusBadge = `<span class="badge bg-danger">CANCELLED</span>`;
                                        checkinBtn = `<div class="small text-danger mt-1">Vé đã bị hủy</div>`;
                                    } else {
                                        ticketStatusBadge = `<span class="badge bg-warning text-dark">UNUSED</span>`;
                                        if (b.status === 'PAID') {
                                            checkinBtn = `
                                                <button class="btn btn-sm btn-success w-100 mt-2 btn-check-in-ticket" data-ticket-id="${t.id}">
                                                    <i class="bi bi-qr-code-scan me-1"></i> Hỗ trợ Check-in
                                                </button>
                                            `;
                                        } else {
                                            checkinBtn = `<div class="small text-muted mt-1">Chỉ check-in khi đơn đã PAID</div>`;
                                        }
                                    }

                                    ticketsContainer.innerHTML += `
                                        <div class="col-sm-6 col-md-4">
                                            <div class="card p-2 border shadow-sm h-100 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-primary">${t.ticket_code}</span>
                                                    ${ticketStatusBadge}
                                                </div>
                                                <div class="small text-muted">Ghế: <strong>${t.booking_seat ? t.booking_seat.seat_code : 'N/A'}</strong></div>
                                                ${checkinBtn}
                                            </div>
                                        </div>
                                    `;
                                });
                            } else {
                                ticketsContainer.innerHTML = '<div class="col-12 text-center text-muted">Không có vé nào.</div>';
                            }

                            // Payments
                            const paymentsTbody = document.getElementById('detail-payments-tbody');
                            paymentsTbody.innerHTML = '';
                            if (b.payment) {
                                paymentsTbody.innerHTML = `
                                    <tr>
                                        <td><strong class="text-uppercase">${b.payment.payment_method}</strong></td>
                                        <td><code>${b.payment.transaction_code || 'N/A'}</code></td>
                                        <td class="text-end fw-bold text-danger">${formatVND(b.payment.amount)}</td>
                                        <td>
                                            <span class="badge bg-${b.payment.status === 'paid' || b.payment.status === 'SUCCESS' ? 'success' : 'warning'}">
                                                ${b.payment.status}
                                            </span>
                                        </td>
                                        <td>${formatDateTime(b.payment.paid_at || b.payment.created_at)}</td>
                                    </tr>
                                `;
                            } else {
                                paymentsTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Không có thông tin giao dịch.</td></tr>';
                            }

                            // Timeline
                            const timeline = document.getElementById('detail-history-timeline');
                            timeline.innerHTML = '';
                            if (res.audit_logs && res.audit_logs.length > 0) {
                                res.audit_logs.forEach(log => {
                                    let timelineClass = '';
                                    if (log.action.includes('CANCEL')) timelineClass = 'cancelled';
                                    if (log.action.includes('CHECK_IN') || log.action.includes('CHECKIN')) timelineClass = 'checkin';

                                    timeline.innerHTML += `
                                        <div class="timeline-item ${timelineClass}">
                                            <div class="fw-bold text-primary">${log.action}</div>
                                            <small class="text-muted d-block">${formatDateTime(log.created_at)} • Bởi: <strong>${log.performed_by}</strong></small>
                                            ${log.new_value && log.new_value.reason ? `<div class="mt-1 bg-white p-2 border rounded small">Lý do: <em>${log.new_value.reason}</em></div>` : ''}
                                        </div>
                                    `;
                                });
                            } else {
                                timeline.innerHTML = `
                                    <div class="timeline-item">
                                        <div class="fw-semibold text-muted">Booking được tạo</div>
                                        <small class="text-muted">${formatDateTime(b.created_at)}</small>
                                    </div>
                                `;
                            }

                            // Footer Buttons
                            const footer = document.getElementById('detail-modal-footer');
                            footer.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>';
                            
                            const hasUsed = b.tickets && b.tickets.some(t => t.status === 'USED');
                            if (b.status !== 'CANCELLED' && b.status !== 'CHECKED_IN' && !hasUsed) {
                                footer.innerHTML += `
                                    <button type="button" class="btn btn-danger btn-trigger-cancel" data-id="${b.id}" data-code="${b.booking_code}">
                                        <i class="bi bi-x-circle me-1"></i> Hủy Booking
                                    </button>
                                `;
                            }

                            const myModal = new bootstrap.Modal(document.getElementById('detailModal'));
                            myModal.show();

                            // Check-in handlers
                            document.querySelectorAll('.btn-check-in-ticket').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    confirmCheckInTicket(this.getAttribute('data-ticket-id'), myModal);
                                });
                            });

                            // Cancel triggers
                            const cancelBtn = footer.querySelector('.btn-trigger-cancel');
                            if (cancelBtn) {
                                cancelBtn.addEventListener('click', function() {
                                    myModal.hide();
                                    setTimeout(() => {
                                        openCancelModal(this.getAttribute('data-id'), this.getAttribute('data-code'));
                                    }, 400);
                                });
                            }
                        } else {
                            showAlert('danger', res.message || 'Không thể tải chi tiết booking.');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showAlert('danger', 'Lỗi tải thông tin chi tiết.');
                    });
            }

            // Check-in confirmation
            function confirmCheckInTicket(ticketId, detailModalInstance) {
                const reason = prompt('Nhập lý do hỗ trợ check-in (bắt buộc):');
                if (reason === null) return; // User clicked Cancel
                if (reason.trim() === '') {
                    alert('Bạn phải nhập lý do check-in!');
                    return;
                }

                fetch(`/admin/api/bookings/tickets/${ticketId}/check-in?tab_token=${tabToken}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ reason: reason })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        alert('Check-in thành công!');
                        detailModalInstance.hide();
                        loadBookings(currentPage);
                    } else {
                        alert(res.message || 'Check-in thất bại.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Lỗi kết nối check-in.');
                });
            }

            // Open cancel modal
            function openCancelModal(id, code) {
                document.getElementById('cancel-booking-id').value = id;
                document.getElementById('cancel-booking-code-text').textContent = code;
                document.getElementById('cancel_reason').value = '';
                
                const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
                cancelModal.show();
            }

            // Submit cancellation
            document.getElementById('cancel-booking-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('cancel-booking-id').value;
                const reason = document.getElementById('cancel_reason').value;
                
                const btn = document.getElementById('btn-confirm-cancel');
                const spinner = document.getElementById('cancel-spinner');
                
                btn.disabled = true;
                spinner.classList.remove('d-none');

                fetch(`/admin/bookings/${id}/cancel?tab_token=${tabToken}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ reason: reason })
                })
                .then(res => res.json())
                .then(res => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    
                    const cancelModalEl = document.getElementById('cancelModal');
                    const cancelModalInstance = bootstrap.Modal.getInstance(cancelModalEl);
                    if (cancelModalInstance) cancelModalInstance.hide();

                    if (res.message.includes('thành công')) {
                        showAlert('success', res.message);
                        loadBookings(currentPage);
                    } else {
                        showAlert('danger', res.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    showAlert('danger', 'Lỗi khi thực hiện hủy booking.');
                });
            });

            // Show Alert
            function showAlert(type, message) {
                const container = document.getElementById('alert-container');
                container.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show shadow-sm">
                        <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }

            // Filter submit & reset
            document.getElementById('filter-form').addEventListener('submit', function(e) {
                e.preventDefault();
                loadBookings(1);
            });

            document.getElementById('btn-reset').addEventListener('click', function() {
                document.getElementById('filter-form').reset();
                loadBookings(1);
            });

            document.getElementById('btn-refresh').addEventListener('click', function() {
                loadBookings(currentPage);
            });

            // Init
            loadBookings();
        });
    </script>
@endsection
