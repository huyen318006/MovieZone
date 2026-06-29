<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class VoucherManageController extends Controller
{
    // Bước 1: Hiển thị danh sách Voucher mã giảm giá kèm lọc tìm kiếm
    public function index(Request $request)
    {
        $query = Voucher::query();
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
            'code' => 'required|string|max:50|unique:vouchers,code',
            'discount_type' => 'required|in:PERCENT,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'usage_limit' => 'required|integer|min:0',
            'usage_per_user' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:ACTIVE,DISABLED,EXPIRED',
        ], [
            'code.required' => 'Mã giảm giá không được trống.',
            'code.unique' => 'Mã giảm giá này đã tồn tại.',
            'discount_value.required' => 'Giá trị giảm không được trống.',
            'discount_value.min' => 'Giá trị giảm không được âm.',
            'min_order_amount.required' => 'Giá trị đơn hàng tối thiểu là bắt buộc.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ]);

        // 2. Tạo bản ghi voucher mới trong CSDL
        $voucher = Voucher::create($validated);

        // 3. Ghi log lịch sử thao tác của Admin
        AuditLogService::log('VOUCHER_CREATE', 'Voucher', $voucher->id, null, $voucher->toArray());

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Thêm mã giảm giá mới thành công.');
    }
    // Bước 4: Hiển thị form chỉnh sửa mã giảm giá
    public function edit($id)
    {
        $voucher = Voucher::findOrFail($id);
        return view('admin.voucher.edit', compact('voucher'));
    }

    // Bước 5: Cập nhật thông tin chỉnh sửa của mã giảm giá
    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        // Lưu trữ lại dữ liệu cũ trước khi thực hiện chỉnh sửa
        $oldData = $voucher->toArray();
        // 1. Xác thực thông tin mới gửi lên (Lưu ý: bỏ qua kiểm duyệt trùng code của chính ID hiện tại)
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $id,
            'discount_type' => 'required|in:PERCENT,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'usage_limit' => 'required|integer|min:0',
            'usage_per_user' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:ACTIVE,DISABLED,EXPIRED',
        ], [
            'code.required' => 'Mã giảm giá không được trống.',
            'code.unique' => 'Mã giảm giá này đã tồn tại.',
            'discount_value.required' => 'Giá trị giảm không được trống.',
            'discount_value.min' => 'Giá trị giảm không được âm.',
            'min_order_amount.required' => 'Giá trị đơn hàng tối thiểu là bắt buộc.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ]);

        // 2. Thực hiện cập nhật dữ liệu vào DB
        $voucher->update($validated);

        // 3. Ghi log cập nhật thông tin kèm dữ liệu đối chiếu
        AuditLogService::log('VOUCHER_UPDATE', 'Voucher', $voucher->id, $oldData, $voucher->fresh()->toArray());

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Cập nhật mã giảm giá thành công.');
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
