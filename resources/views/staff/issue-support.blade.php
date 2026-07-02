@extends('layout.staff')

@section('title', 'Hỗ trợ sự cố đặt vé')
@section('page-title', 'Hỗ trợ sự cố đặt vé')

@section('content')
<div id="issueSupportApp" class="container-fluid p-0">

    {{-- SEARCH BAR (Đồng bộ phong cách với booking-lookup) --}}
    <div class="card border-0 shadow-sm mb-4 lookup-search-card">
        <div class="card-body p-4">
            <div class="row g-3 align-items-center search-row">
                <div class="col-12 col-md-3 search-type-wrap">
                    <select id="issueInputType" class="form-select form-select-lg search-type-select" style="border-radius: 8px; font-size: 14px; font-weight: 600;">
                        <option value="booking_code">Mã Booking</option>
                        <option value="ticket_code">Mã Vé / QR</option>
                        <option value="phone">Số điện thoại</option>
                        <option value="email">Email</option>
                        <option value="qr_content">Nội dung QR</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 search-input-wrap position-relative">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                            <i class="bi bi-search search-icon"></i>
                        </span>
                        <input type="text" id="issueInputValue" class="form-control border-start-0 search-input" 
                               placeholder="Nhập mã booking, mã vé/QR, sđt hoặc email..." 
                               autocomplete="off" spellcheck="false" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px; font-size: 15px;">
                    </div>
                    <div id="recentIssueSearchesDropdown" class="recent-searches-dropdown position-absolute w-100 shadow-sm bg-white" style="display:none; z-index: 1000; border-radius: 8px;"></div>
                </div>

                <div class="col-12 col-md-3 d-grid">
                    <button type="button" id="btnIssueDiagnose" class="btn btn-primary btn-lg btn-search" disabled style="border-radius: 8px; font-size: 15px; font-weight: 600;">
                        <i class="bi bi-shield-check me-2"></i> Xác định sự cố
                    </button>
                </div>
            </div>

            <div id="issueValidationError" class="alert alert-danger mt-3 validation-error" style="display:none; border-radius: 8px; font-size: 13px;"></div>

            <div class="shortcut-hints mt-3 d-flex gap-3 text-muted" style="font-size: 12px;">
                <span class="shortcut-badge"><kbd class="bg-secondary px-2 py-1">Ctrl</kbd> + <kbd class="bg-secondary px-2 py-1">K</kbd> Focus tìm kiếm</span>
                <span class="shortcut-badge"><kbd class="bg-secondary px-2 py-1">Enter</kbd> Xác định sự cố</span>
            </div>
        </div>
    </div>

    {{-- STATE: EMPTY --}}
    <div id="stateEmpty" class="card border-0 shadow-sm text-center py-5 state-card">
        <div class="card-body py-4">
            <div class="state-illustration mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 90px; height: 90px;">
                    <i class="bi bi-patch-question text-primary" style="font-size: 42px; opacity: 0.8;"></i>
                </div>
            </div>
            <h5 class="fw-bold text-dark mb-2">Trung Tâm Xử Lý & Hỗ Trợ Sự Cố</h5>
            <p class="text-muted mx-auto mb-0" style="max-width: 500px; font-size: 14px; line-height: 1.6;">
                Vui lòng nhập thông tin tìm kiếm phía trên. Hệ thống sẽ tự động đối soát chéo dữ liệu và đề xuất quy trình xử lý chuẩn.
            </p>
        </div>
    </div>

    {{-- STATE: LOADING --}}
    <div id="stateLoading" class="card border-0 shadow-sm text-center py-5 state-card" style="display:none;">
        <div class="card-body py-5">
            <div class="loading-spinner mb-3">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem; border-width: 0.25em;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <h6 class="fw-bold text-primary mb-1">Đang truy vấn dữ liệu...</h6>
            <p class="text-muted small mb-0">Hệ thống đang kiểm tra trạng thái thanh toán và thông tin vé liên quan</p>
        </div>
    </div>

    {{-- STATE: RESULT CARD (Bố cục chi tiết chuẩn Booking Lookup) --}}
    <div id="issueResultCard" class="issue-result-wrapper" style="display:none;">
        <div class="row g-4">
            
            {{-- Cột Trái: Kết quả chẩn đoán và Hướng dẫn --}}
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm mb-4" style="border-left: 5px solid var(--bs-warning) !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
<span class="badge bg-warning text-dark px-2.5 py-1.5 fw-bold" id="issueType" style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">N/A</span>
                            <span class="text-muted small">| Kết quả phân tích hệ thống</span>
                        </div>
                        <h4 class="fw-bold text-dark mb-2" id="issueTitle">N/A</h4>
<div class="p-3 rounded text-secondary" id="issueSummary" style="font-size: 13.5px; line-height: 1.6; border-left: 3px solid #dee2e6; background: var(--staff-bg) !important; color: var(--staff-text) !important;">
                            —
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold text-uppercase text-primary m-0 d-flex align-items-center gap-2" style="font-size: 12px; letter-spacing: 1px;">
                            <i class="bi bi-journal-check fs-5"></i> Quy trình hướng dẫn xử lý (UC-STAFF-04)
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <ul id="issueActions" class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size: 14px; color: var(--staff-text);">
                            </ul>
                        
                        <hr class="my-4 text-muted opacity-20">
                        
                        <div class="d-flex gap-3 align-items-start p-3 bg-light-danger text-danger rounded" style="font-size: 12.5px; background-color: #fff8f8; border: 1px dashed #f8d7da;">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-0.5"></i>
                            <div>
                                <strong class="d-block mb-1">Lưu ý phân quyền nghiệp vụ:</strong>
                                Nếu trường hợp cần can thiệp vượt thẩm quyền nhân viên vận hành (như: hoàn tiền trực tiếp, điều chỉnh thủ công trạng thái cổng thanh toán, khôi phục booking đã hủy rác) &rarr; Vui lòng lập biên bản ghi nhận sự cố và chuyển tiếp bộ phận <strong>Admin hệ thống</strong> xử lý.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cột Phải: Tóm tắt thông tin Booking liên quan --}}
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm position-sticky" style="top: 24px;">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h6 class="fw-bold text-uppercase text-secondary m-0 d-flex align-items-center gap-2" style="font-size: 12px; letter-spacing: 1px;">
                            <i class="bi bi-ticket-perforated fs-5"></i> Tóm tắt thông tin giao dịch
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-3">
                            
                            <div class="d-flex justify-content-between align-items-center pb-2.5 border-bottom border-light">
                                <span class="text-muted small">Mã Booking</span>
<span class="fw-bold" id="bookingCode" style="font-size: 14px; color: var(--staff-text) !important;">N/A</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pb-2.5 border-bottom border-light">
                                <span class="text-muted small">Trạng thái vé</span>
                                <span class="badge bg-light text-dark fw-bold px-2 py-1" id="bookingStatus" style="font-size: 12px;">N/A</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pb-2.5 border-bottom border-light">
                                <span class="text-muted small">Trạng thái thanh toán</span>
                                <span class="badge bg-light text-dark fw-bold px-2 py-1" id="paymentStatus" style="font-size: 12px;">N/A</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pb-2.5 border-bottom border-light">
                                <span class="text-muted small">Thời hạn giữ chỗ</span>
                                <span class="text-secondary fw-semibold small" id="expiredAt">N/A</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2">
                                <span class="text-dark fw-bold">Tổng tiền giá trị</span>
                                <span class="text-primary fw-extrabold fs-5" id="finalAmount" style="font-weight: 800;">0đ</span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
const API_BASE = '{{ url("staff/api/issue-support") }}';
const API_DIAGNOSE_URL = `${API_BASE}/diagnose`;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

const stateEmpty = document.getElementById('stateEmpty');
const stateLoading = document.getElementById('stateLoading');
const issueResultCard = document.getElementById('issueResultCard');

const issueInputType = document.getElementById('issueInputType');
const issueInputValue = document.getElementById('issueInputValue');
const btnIssueDiagnose = document.getElementById('btnIssueDiagnose');
const issueValidationError = document.getElementById('issueValidationError');

function showState(name){
    stateEmpty.style.display = name === 'empty' ? 'block' : 'none';
    stateLoading.style.display = name === 'loading' ? 'block' : 'none';
}

function showValidationError(msg){
    issueValidationError.textContent = '⚠️ ' + msg;
    issueValidationError.style.display = 'block';
}

function hideValidationError(){
    issueValidationError.style.display = 'none';
}

const VALIDATORS = {
    booking_code: /^BK-\d{8}-\d{3,}$/,
    ticket_code: /^TK-\d{8}-\d{3,}$/,
    phone: /^(0|\+84)(3|5|7|8|9)\d{8}$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    qr_content: /^MZ\|.+\|.{12}$/,
};

function validateClient(){
    const type = issueInputType.value;
    const val = issueInputValue.value.trim();
    if(!val) return false;

    const re = VALIDATORS[type];
    if(re && !re.test(val)){
        showValidationError('Dữ liệu không đúng định dạng cho loại: ' + type);
        return false;
    }

    hideValidationError();
    return true;
}

issueInputValue.addEventListener('input', ()=>{
    btnIssueDiagnose.disabled = issueInputValue.value.trim().length === 0;
    hideValidationError();
});

document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        issueInputValue.focus();
        issueInputValue.select();
    }
});


issueInputType.addEventListener('change', ()=>{
    hideValidationError();
});

btnIssueDiagnose.addEventListener('click', ()=>diagnose());

issueInputValue.addEventListener('keydown', (e)=>{
    if(e.key === 'Enter') diagnose();
});

async function diagnose(){
    const type = issueInputType.value;
    const value = issueInputValue.value.trim();

    if(!validateClient()) return;

    showState('loading');
    issueResultCard.style.display = 'none';

    try{
        const resp = await fetch(`${API_BASE}/diagnose`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ input_type: type, input_value: value })
        });

        const json = await resp.json();
        if(!resp.ok || !json.success){
            throw new Error(json?.issue?.title || 'Lỗi hệ thống');
        }

        render(json);
        showState('empty');

    }catch(err){
        showState('empty');
        showValidationError(err.message || 'Không thể tra cứu');
    }
}

function fmt(dt){
    if(!dt) return 'N/A';
    try{ return new Date(dt).toLocaleString('vi-VN'); }catch(e){ return dt; }
}

function render(json){
    const issue = json.issue;

    document.getElementById('issueType').textContent = issue.type || 'N/A';
    document.getElementById('issueTitle').textContent = issue.title || 'N/A';
    document.getElementById('issueSummary').textContent = issue.summary || '';

    document.getElementById('bookingCode').textContent = json.booking?.booking_code || 'N/A';
    document.getElementById('bookingStatus').textContent = json.booking?.status || 'N/A';
    document.getElementById('paymentStatus').textContent = json.booking?.payment_status || 'N/A';
    document.getElementById('expiredAt').textContent = fmt(json.booking?.expired_at);
    document.getElementById('finalAmount').textContent = json.booking?.final_amount ? Number(json.booking.final_amount).toLocaleString('vi-VN') + 'đ' : '0đ';

    const actionsEl = document.getElementById('issueActions');
    actionsEl.innerHTML = '';
    (issue.actions || []).forEach(a =>{
        const li = document.createElement('li');
        li.textContent = a;
        li.style.marginBottom = '6px';
        actionsEl.appendChild(li);
    });

    issueResultCard.style.display = 'block';
}

</script>
@endpush