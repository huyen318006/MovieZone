<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

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
        $validated = $this->validateVoucher($request);
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
            $validated = $this->validateVoucher($request, $id);

            $validated['code'] = strtoupper($validated['code']);
            $voucher->update($validated);

        } else {
            // --- NHÁNH B: Voucher đã có người dùng → chỉ cho phép sửa trường an toàn ---
            $rules = [
                'status'   => 'required|in:ACTIVE,DISABLED,EXPIRED',
                'end_date' => ['required', 'date', 'after:' . $voucher->start_date->format('Y-m-d')],
                'usage_limit' => ['required', 'integer', 'not_in:0', 'min:-1', 'max:1000000'],
                'usage_per_user' => 'required|integer|min:1',
            ];

            $validated = $request->validate($rules, [
                'end_date.after'  => 'Ngày kết thúc phải sau ngày bắt đầu.',
                'usage_limit.min' => 'Giá trị tổng lượt sử dụng không hợp lệ.',
                'usage_limit.max' => 'Giới hạn tổng lượt sử dụng không được vượt quá 1.000.000.',
            ]);

            // Nếu voucher đã được sử dụng, ensure admin cannot reduce usage_limit below used count
            if ((int) $validated['usage_limit'] !== -1 && (int) $validated['usage_limit'] < $usageCount) {
                return back()
                    ->withInput()
                    ->with('error', "Giới hạn tổng lượt dùng không được nhỏ hơn số lượt đã sử dụng thực tế ({$usageCount} lượt).");
            }

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
    private function validateVoucher(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('vouchers', 'code')->ignore($id),
            ],

            'discount_type' => 'required|in:PERCENT,FIXED',

            'discount_value' => 'required|numeric',

            'max_discount' => [
                Rule::requiredIf($request->discount_type === 'PERCENT'),
                'nullable',
                'numeric',
                'min:1000',
                'max:1000000',
            ],

            'min_order_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:10000000',
            ],

            'usage_limit' => [
                'required',
                'integer',
                'not_in:0',
                'min:-1',
                'max:10000',
            ],

            'usage_per_user' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after:start_date',

            'status' => 'required|in:ACTIVE,DISABLED,EXPIRED',

        ], [

            // Code
            'code.required' => 'Mã giảm giá không được để trống.',
            'code.max' => 'Mã giảm giá tối đa 50 ký tự.',
            'code.regex' => 'Mã giảm giá chỉ được chứa chữ IN HOA, số, dấu gạch ngang hoặc gạch dưới.',
            'code.unique' => 'Mã giảm giá này đã tồn tại.',

            // Discount type
            'discount_type.required' => 'Vui lòng chọn loại giảm giá.',

            // Discount value
            'discount_value.required' => 'Giá trị giảm không được để trống.',
            'discount_value.numeric' => 'Giá trị giảm phải là số.',

            // Max discount
            'max_discount.required' => 'Vui lòng nhập mức giảm tối đa cho voucher phần trăm.',
            'max_discount.numeric' => 'Mức giảm tối đa phải là số.',
            'max_discount.min' => 'Mức giảm tối đa phải từ 1.000 đồng.',
            'max_discount.max' => 'Mức giảm tối đa không được vượt quá 1.000.000 đồng.',

            // Min order
            'min_order_amount.required' => 'Giá trị đơn hàng tối thiểu là bắt buộc.',
            'min_order_amount.numeric' => 'Giá trị đơn hàng tối thiểu phải là số.',
            'min_order_amount.max' => 'Giá trị đơn hàng tối thiểu không được vượt quá 10.000.000 đồng.',

            // Usage
            'usage_limit.required' => 'Vui lòng nhập tổng lượt sử dụng.',
            'usage_limit.min' => 'Giá trị tổng lượt sử dụng không hợp lệ.',
            'usage_limit.max' => 'Tổng lượt sử dụng không được vượt quá 10.000 lượt.',

            'usage_per_user.required' => 'Vui lòng nhập số lượt sử dụng mỗi người.',
            'usage_per_user.min' => 'Mỗi người phải được sử dụng ít nhất 1 lần.',
            'usage_per_user.max' => 'Mỗi người chỉ được sử dụng tối đa 5 lần.',

            // Date
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.after_or_equal' => 'Ngày bắt đầu không được nằm trong quá khứ.',

            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',

            // Status
            'status.required' => 'Vui lòng chọn trạng thái voucher.',
        ]);

        $validator->after(function ($validator) use ($request) {

            // Voucher %
            if ($request->discount_type === 'PERCENT') {

                if ($request->discount_value < 1) {
                    $validator->errors()->add(
                        'discount_value',
                        'Giá trị giảm theo phần trăm phải từ 1%.'
                    );
                }

                if ($request->discount_value > 100) {
                    $validator->errors()->add(
                        'discount_value',
                        'Giảm theo phần trăm không được vượt quá 100%.'
                    );
                }
            }

            // Voucher tiền cố định
            if ($request->discount_type === 'FIXED') {

                if ($request->discount_value < 1000) {
                    $validator->errors()->add(
                        'discount_value',
                        'Giá trị giảm tối thiểu là 1.000 đồng.'
                    );
                }

                if ($request->discount_value > 1000000) {
                    $validator->errors()->add(
                        'discount_value',
                        'Giá trị giảm không được vượt quá 1.000.000 đồng.'
                    );
                }

                if ($request->discount_value > $request->min_order_amount) {
                    $validator->errors()->add(
                        'discount_value',
                        'Giá trị giảm không được lớn hơn giá trị đơn hàng tối thiểu.'
                    );
                }
            }
        });

        return $validator->validate();
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
