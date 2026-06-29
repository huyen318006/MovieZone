<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class VoucherManageController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vouchers = $query->paginate(10)->appends($request->all());

        return view('admin.voucher.index', compact('vouchers'));
    }

    public function create()
    {
        return view('admin.voucher.create');
    }

    public function store(Request $request)
    {
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

        $voucher = Voucher::create($validated);

        // Audit Log
        AuditLogService::log('VOUCHER_CREATE', 'Voucher', $voucher->id, null, $voucher->toArray());

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Thêm mã giảm giá mới thành công.');
    }

    public function edit($id)
    {
        $voucher = Voucher::findOrFail($id);
        return view('admin.voucher.edit', compact('voucher'));
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $oldData = $voucher->toArray();

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

        $voucher->update($validated);

        // Audit Log
        AuditLogService::log('VOUCHER_UPDATE', 'Voucher', $voucher->id, $oldData, $voucher->fresh()->toArray());

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Cập nhật mã giảm giá thành công.');
    }

    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);

        // Check if voucher has been used
        if ($voucher->usages()->count() > 0) {
            return redirect()->route('admin.vouchers.index')
                ->with('error', 'Không thể xóa mã giảm giá này vì nó đã được sử dụng trong các giao dịch.');
        }

        $oldData = $voucher->toArray();
        $voucher->delete();

        // Audit Log
        AuditLogService::log('VOUCHER_DELETE', 'Voucher', $id, $oldData, null);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Xóa mã giảm giá thành công.');
    }
}
