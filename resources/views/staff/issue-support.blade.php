@extends('layout.staff')

@section('title', 'Hỗ trợ sự cố đặt vé')
@section('page-title', 'Hỗ trợ sự cố đặt vé')

@section('content')
<div class="container" style="max-width:1100px;">

    <div class="lookup-search-card" style="margin-top:10px;">
        <div class="search-row" style="align-items:flex-start;">
            <div class="search-type-wrap">
                <label style="display:block; font-size:12px; color:var(--staff-text-muted); margin-bottom:6px; font-weight:600;">Loại thông tin</label>
                <select id="issueInputType" class="search-type-select" style="min-width:220px;">
                    <option value="booking_code">Mã booking (BK-...)</option>
                    <option value="ticket_code">Mã vé/QR (TK-...)</option>
                    <option value="qr_content">Nội dung QR (MZ|...)</option>
                    <option value="phone">Số điện thoại</option>
                    <option value="email">Email</option>
                </select>
            </div>

            <div class="search-input-wrap" style="margin-top:0;">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="issueInputValue" class="search-input" placeholder="Nhập mã booking/mã vé/QR content/sdt/email" autocomplete="off" spellcheck="false">
            </div>

            <button class="btn-search" id="btnIssueDiagnose" type="button" disabled style="height:44px; margin-top:0;">
                <i class="bi bi-search"></i> Xác định sự cố
            </button>
        </div>

        <div id="issueValidationError" class="validation-error" style="display:none; margin-top:12px;"></div>

        <div style="margin-top:14px; color:var(--staff-text-muted); font-size:13px;">
            Luồng Staff UC-STAFF-04: Staff tra cứu & giải thích tình trạng. Không tự hoàn tiền/sửa payment/khôi phục booking (E2 nếu cần).
        </div>
    </div>

    <div class="state-card" id="stateEmpty" style="margin-top:16px;">
        <div class="state-illustration">
            <i class="bi bi-life-preserver" style="font-size:64px; color: var(--staff-primary); opacity:0.5;"></i>
        </div>
        <h3>Chọn thông tin & xác định sự cố</h3>
        <p>Nhập mã booking, mã vé/QR, hoặc thông tin liên hệ để hệ thống đề xuất hướng dẫn phù hợp.</p>
    </div>

    <div class="state-card" id="stateLoading" style="margin-top:16px; display:none;">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <p style="margin-top:16px; color:var(--staff-text-muted);">Đang tra cứu...</p>
    </div>

    <div class="lookup-filter-card" id="issueResultCard" style="margin-top:16px; display:none;">
        <div class="filter-row">
            <div style="flex:1; min-width:280px;">
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; color:var(--staff-primary); font-weight:800; margin-bottom:8px;">
                    Kết quả xác định sự cố
                </div>
                <div id="issueType" style="font-size:16px; font-weight:800; color:var(--staff-text);"></div>
                <div id="issueTitle" style="margin-top:6px; font-size:15px; font-weight:700; color:var(--staff-text-muted);"></div>
                <div id="issueSummary" style="margin-top:10px; color:var(--staff-text); font-size:13px;"></div>
            </div>
            <div style="flex:1; min-width:280px;">
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; color:var(--staff-primary); font-weight:800; margin-bottom:8px;">
                    Booking/Vé (tóm tắt)
                </div>
                <div style="color:var(--staff-text-muted); font-size:13px;">
                    <div><b>Mã:</b> <span id="bookingCode">N/A</span></div>
                    <div><b>Trạng thái booking:</b> <span id="bookingStatus">N/A</span></div>
                    <div><b>Thanh toán:</b> <span id="paymentStatus">N/A</span></div>
                    <div><b>Hạn:</b> <span id="expiredAt">N/A</span></div>
                    <div><b>Giá trị:</b> <span id="finalAmount">0</span></div>
                </div>
            </div>
        </div>

        <div style="margin-top:16px;">
            <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; color:var(--staff-primary); font-weight:800; margin-bottom:8px;">
                Hướng dẫn xử lý (đúng luồng UC-STAFF-04)
            </div>
            <ul id="issueActions" style="margin:0; padding-left:18px; color:var(--staff-text); font-size:13px;">
            </ul>
        </div>

        <div style="margin-top:16px; color:var(--staff-text-muted); font-size:12px;">
            Nếu cần can thiệp vượt quyền (hoàn tiền/chỉnh payment/khôi phục booking) → ghi nhận & chuyển Admin.
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const API_BASE = '{{ url("staff/api/issue-support") }}';
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
        actionsEl.appendChild(li);
    });

    issueResultCard.style.display = 'block';
}
</script>
@endpush

