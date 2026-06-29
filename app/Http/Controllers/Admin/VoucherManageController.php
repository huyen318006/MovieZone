<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherManageController extends Controller
{
    // Bước 1: Hiển thị danh sách Voucher mã giảm giá kèm lọc tìm kiếm
    public function index(Request $request)
    {
        $query = Voucher::withCount('usages'); // Lấy số lượt đã dùng thực tế để hiển thị trên danh sách
        // Lọc tìm kiếm theo mã giảm giá (Code)
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }
        // Lọc theo trạng thái (ACTIVE, DISABLED, EXPIRED)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vouchers = $query->paginate(10)->appends($request->all());

        return view('admin.voucher.index', compact('vouchers'));
    }
    // Bước 2: Hiển thị giao diện thêm Voucher mới
    public function create()
    {
        return view('admin.voucher.create');
    }
    // Bước 3: Lưu mã giảm giá mới
    public function store(Request $request)
    {
        // 1. Xác thực thông tin nhập vào (lưu ý: kiểm tra unique mã code trên toàn hệ thống bảng vouchers)
        $validated = $request->validate([
            // Chỉ chấp nhận chữ HOA, số, gạch ngang/dưới; duy nhất trong hệ thống
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                'unique:vouchers,code',
            ],
            'discount_type' => 'required|in:PERCENT,FIXED',
            // Nếu là PERCENT thì giá trị giảm không được vượt quá 100
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($request->discount_type === 'PERCENT', 'max:100'),
            ],
            'max_discount'     => 'nullable|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            // Giới hạn hợp lý tối đa 1 triệu lượt
            'usage_limit'    => 'required|integer|min:0|max:1000000',
            'usage_per_user' => 'required|integer|min:1',
            // start_date không được là ngày trong quá khứ khi tạo mới
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after:start_date',
            'status'     => 'required|in:ACTIVE,DISABLED,EXPIRED',
        ], [
            'code.required'             => 'Mã giảm giá không được trống.',
            'code.regex'                => 'Mã giảm giá chỉ được chứa chữ IN HOA, số, dấu gạch ngang hoặc gạch dưới (VD: STUDENT10, VIP-2025).',
            'code.unique'               => 'Mã giảm giá này đã tồn tại trong hệ thống.',
            'discount_value.required'   => 'Giá trị giảm không được trống.',
            'discount_value.min'        => 'Giá trị giảm không được âm.',
            'discount_value.max'        => 'Giảm theo phần trăm (%) không được vượt quá 100%.',
            'min_order_amount.required' => 'Giá trị đơn hàng tối thiểu là bắt buộc.',
            'usage_limit.max'           => 'Giới hạn tổng lượt dùng không được vượt quá 1.000.000.',
            'start_date.required'       => 'Ngày bắt đầu là bắt buộc.',
            'start_date.after_or_equal' => 'Ngày bắt đầu không được là ngày trong quá khứ.',
            'end_date.required'         => 'Ngày kết thúc là bắt buộc.',
            'end_date.after'            => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ]);

        // 2. Chuẩn hóa: Tự động chuyển code sang chữ HOA
        $validated['code'] = strtoupper($validated['code']);

        // 3. Tạo bản ghi voucher mới trong CSDL
        $voucher = Voucher::create($validated);

        // 3. Ghi log lịch sử thao tác của Admin
        AuditLogService::log('VOUCHER_CREATE', 'Voucher', $voucher->id, null, $voucher->toArray());

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Thêm mã giảm giá mới thành công.');
    }
    // Bước 4: Hiển thị form chỉnh sửa mã giảm giá
    public function edit($id)
    {
        // Lấy thêm số lượt đã dùng để view quyết định khóa/mở trường nhập liệu
        $voucher = Voucher::withCount('usages')->findOrFail($id);
        return view('admin.voucher.edit', compact('voucher'));
    }

    // Bước 5: Cập nhật thông tin chỉnh sửa của mã giảm giá
    public function update(Request $request, $id)
    {
        $voucher     = Voucher::findOrFail($id);
        $usageCount  = $voucher->usages()->count(); // Số lượt đã sử dụng thực tế
        $hasBeenUsed = $usageCount > 0;
        // Lưu trữ lại dữ liệu cũ trước khi thực hiện chỉnh sửa
        $oldData = $voucher->toArray();

        if (!$hasBeenUsed) {
            // --- NHÁNH A: Voucher chưa ai dùng → cho phép sửa toàn bộ ---
            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[A-Z0-9_-]+$/',
                    "unique:vouchers,code,{$id}", // Bỏ qua chính ID hiện tại khi kiểm tra unique
                ],
                'discount_type'  => 'required|in:PERCENT,FIXED',
                'discount_value' => [
                    'required',
                    'numeric',
                    'min:0',
                    Rule::when($request->discount_type === 'PERCENT', 'max:100'),
                ],
                'max_discount'     => 'nullable|numeric|min:0',
                'min_order_amount' => 'required|numeric|min:0',
                'usage_limit'      => 'required|integer|min:0|max:1000000',
                'usage_per_user'   => 'required|integer|min:1',
                'start_date'       => 'required|date',
                'end_date'         => 'required|date|after:start_date',
                'status'           => 'required|in:ACTIVE,DISABLED,EXPIRED',
            ], [
                'code.regex'          => 'Mã giảm giá chỉ được chứa chữ IN HOA, số, gạch ngang hoặc gạch dưới.',
                'code.unique'         => 'Mã giảm giá này đã tồn tại.',
                'discount_value.max'  => 'Giảm theo % không được vượt quá 100%.',
                'end_date.after'      => 'Ngày kết thúc phải sau ngày bắt đầu.',
                'usage_limit.max'     => 'Giới hạn tổng lượt dùng không được vượt quá 1.000.000.',
            ]);

            $validated['code'] = strtoupper($validated['code']);
            $voucher->update($validated);

        } else {
            // --- NHÁNH B: Voucher đã có người dùng → chỉ cho phép sửa trường an toàn ---
            $validated = $request->validate([
                'status'   => 'required|in:ACTIVE,DISABLED,EXPIRED',
                'end_date' => ['required', 'date', 'after:' . $voucher->start_date->format('Y-m-d')],
                // Giới hạn tổng lượt dùng chỉ được TĂNG, không được giảm xuống dưới số lượt đã dùng
                'usage_limit' => [
                    'required',
                    'integer',
                    "min:{$usageCount}",
                    'max:1000000',
                ],
                'usage_per_user' => 'required|integer|min:1',
            ], [
                'end_date.after'  => 'Ngày kết thúc phải sau ngày bắt đầu.',
                'usage_limit.min' => "Giới hạn tổng lượt dùng không được nhỏ hơn số lượt đã sử dụng thực tế ({$usageCount} lượt).",
                'usage_limit.max' => 'Giới hạn tổng lượt dùng không được vượt quá 1.000.000.',
            ]);

            $voucher->update($validated);
        }

        // Cảnh báo mâu thuẫn nếu đổi status về ACTIVE nhưng end_date đã qua
        $warningMsg = null;
        if ($request->status === 'ACTIVE' && $voucher->fresh()->end_date->isPast()) {
            $warningMsg = 'Cảnh báo: Voucher được đặt ACTIVE nhưng ngày kết thúc đã qua. Vui lòng kiểm tra lại.';
        }

        // Ghi log cập nhật thông tin kèm dữ liệu đối chiếu
        AuditLogService::log('VOUCHER_UPDATE', 'Voucher', $voucher->id, $oldData, $voucher->fresh()->toArray());

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Cập nhật mã giảm giá thành công.')
            ->with('warning', $warningMsg);
    }

    // Bước 6: Xóa mã giảm giá khỏi hệ thống
    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);

        // 1. KIỂM TRA RÀNG BUỘC NGHIỆP VỤ: Đảm bảo mã giảm giá này chưa từng được áp dụng sử dụng thành công trong lịch sử đặt vé
        if ($voucher->usages()->count() > 0) {
            return redirect()->route('admin.vouchers.index')
                ->with('error', 'Không thể xóa mã giảm giá này vì nó đã được sử dụng trong các giao dịch.');
        }
        // Lưu thông tin bản ghi trước khi xóa để làm log đối chiếu lịch sử
        $oldData = $voucher->toArray();
        // 2. Thực hiện xóa bản ghi khỏi CSDL
        $voucher->delete();
        // 3. Ghi nhận log thao tác xóa
        AuditLogService::log('VOUCHER_DELETE', 'Voucher', $id, $oldData, null);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Xóa mã giảm giá thành công.');
    }
}
