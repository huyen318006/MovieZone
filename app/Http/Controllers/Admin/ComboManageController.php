<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Product;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComboManageController extends Controller
{
    // Bước 1: Hiển thị danh sách Combo bắp nước (kèm tìm kiếm, lọc và phân trang)
    public function index(Request $request)
    {
        // Khởi tạo truy vấn earger loading mối quan hệ 'products' để tối ưu số lượng câu lệnh SQL (tránh lỗi N+1)
        $query = Combo::with('products');

        // Tìm kiếm theo tên hoặc mô tả nếu có nhập từ khóa
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

         // Lọc theo trạng thái (ACTIVE, INACTIVE) nếu admin chọn
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Phân trang 10 dòng/trang và đính kèm các tham số tìm kiếm/lọc trên URL phân trang
        $combos = $query->paginate(10)->appends($request->all());

        return view('admin.combo.index', compact('combos'));
    }

    // Bước 2: Hiển thị màn hình tạo mới Combo
    public function create()
    {
        // Lấy toàn bộ sản phẩm lẻ (bắp, nước, snack) đang hoạt động để hiển thị trong form chọn thành phần của combo
        $products = Product::where('status', 'ACTIVE')->get();
        return view('admin.combo.create', compact('products'));
    }

    // Bước 3: Lưu thông tin Combo mới vào DB
    public function store(Request $request)
    {
         // 1. Xác thực dữ liệu đầu vào (Validation)
        $validated = $request->validate([
            // Thêm unique để tránh 2 combo cùng tên gây nhầm lẫn
            'name'        => 'required|string|max:150|unique:combos,name',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0|max:99999999.99',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status'      => 'required|in:ACTIVE,INACTIVE',
            // Giới hạn tối đa 10 sản phẩm lẻ trong một combo
            'product_ids'   => 'required|array|min:1|max:10',
            // Mỗi product_id phải tồn tại và không trùng nhau
            'product_ids.*' => 'required|integer|distinct|exists:products,id',
            // Validate từng phần tử quantity trong mảng
            'quantities'    => 'required|array',
            'quantities.*'  => 'required|integer|min:1|max:100',
        ], [
            'name.required'          => 'Tên combo không được trống.',
            'name.unique'            => 'Tên combo này đã tồn tại. Vui lòng chọn tên khác.',
            'price.required'         => 'Giá combo không được trống.',
            'price.numeric'          => 'Giá combo phải là số.',
            'price.min'              => 'Giá combo không được âm.',
            'status.required'        => 'Trạng thái không được trống.',
            'product_ids.required'   => 'Bạn phải chọn ít nhất một sản phẩm lẻ cho combo.',
            'product_ids.min'        => 'Combo phải có ít nhất 1 sản phẩm lẻ.',
            'product_ids.max'        => 'Combo chỉ được phép chứa tối đa 10 sản phẩm lẻ.',
            'product_ids.*.distinct' => 'Bạn đang chọn trùng lặp cùng một sản phẩm lẻ. Vui lòng gộp lại thành một dòng.',
            'product_ids.*.exists'   => 'Một hoặc nhiều sản phẩm lẻ được chọn không hợp lệ hoặc không tồn tại.',
            'quantities.*.required'  => 'Số lượng sản phẩm không được để trống.',
            'quantities.*.integer'   => 'Số lượng sản phẩm phải là số nguyên.',
            'quantities.*.min'       => 'Số lượng mỗi sản phẩm lẻ trong combo phải tối thiểu là 1.',
            'quantities.*.max'       => 'Số lượng mỗi sản phẩm lẻ không được vượt quá 100.',
        ]);

        // Kiểm tra không có sản phẩm INACTIVE hoặc OUT_OF_STOCK được thêm vào combo
        $inactiveProducts = Product::whereIn('id', $validated['product_ids'])
            ->where('status', '!=', 'ACTIVE')
            ->pluck('name');

        if ($inactiveProducts->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'product_ids' => 'Các sản phẩm sau không thể thêm vào combo vì không còn hoạt động: ' . $inactiveProducts->join(', ') . '.',
            ]);
        }

         // 2. Xử lý upload file ảnh sản phẩm nếu có
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('combos', 'public');
        }
        // 3. Khởi tạo Database Transaction để đảm bảo tính toàn vẹn dữ liệu (hoặc lưu hết hoặc không lưu gì nếu lỗi)
        DB::beginTransaction();
        try {
             // 3.1. Tạo mới bản ghi Combo trong bảng `combos`
            $combo = Combo::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'image_url' => $imageUrl,
                'status' => $validated['status'],
            ]);

            // 3.2. Chuẩn bị dữ liệu để đồng bộ bảng trung gian `combo_items`
            $syncData = [];
            foreach ($validated['product_ids'] as $productId) {
                // Lấy số lượng tương ứng của từng sản phẩm lẻ, mặc định là 1 nếu không nhập
                $quantity = (int) ($validated['quantities'][$productId] ?? 1);
                if ($quantity > 0) {
                    $syncData[$productId] = ['quantity' => $quantity];
                }
            }
            // Kiểm tra sau khi lọc vẫn còn ít nhất 1 sản phẩm hợp lệ
            if (empty($syncData)) {
                throw new \Exception('Combo phải có ít nhất một sản phẩm với số lượng > 0.');
            }

            // Đồng bộ dữ liệu sang bảng trung gian `combo_items`
            $combo->products()->sync($syncData);
            // 3.3. Nếu mọi câu lệnh thành công, xác nhận lưu vĩnh viễn vào DB
            DB::commit();

            // 4. Ghi nhận lịch sử hoạt động (Audit Log)
            AuditLogService::log('COMBO_CREATE', 'Combo', $combo->id, null, [
                'combo' => $combo->toArray(),
                'items' => $syncData
            ]);

            return redirect()->route('admin.combos.index')
                ->with('success', 'Thêm combo bắp nước thành công.');

        } catch (\Exception $e) {
            // 5. Nếu gặp bất kỳ lỗi nào, hoàn tác toàn bộ thao tác ghi vào DB
            DB::rollBack();
            // Xóa file ảnh vừa tải lên nếu giao dịch thất bại để tránh rác server
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // Bước 4: Hiển thị giao diện chỉnh sửa thông tin Combo
    public function edit($id)
    {
        // Tìm combo cần sửa kèm các sản phẩm lẻ đang liên kết hoặc trả về 404 nếu không tìm thấy
        $combo = Combo::with('products')->findOrFail($id);
        // Chỉ lấy sản phẩm ACTIVE, nhưng vẫn thêm các sản phẩm đang liên kết với combo (dù INACTIVE)
        // để admin thấy được danh sách hiện tại và có thể gỡ ra
        $products = Product::where('status', 'ACTIVE')
            ->orWhereIn('id', $combo->products->pluck('id'))
            ->get();

        // Tạo mảng dạng key-value [id_sản_phẩm => số_lượng] từ pivot để fill vào form chỉnh sửa
        $selectedProducts = $combo->products->pluck('pivot.quantity', 'id')->toArray();

        return view('admin.combo.edit', compact('combo', 'products', 'selectedProducts'));
    }

    // Bước 5: Cập nhật thông tin sửa đổi của Combo vào Cơ sở dữ liệu
    public function update(Request $request, $id)
    {
        // Tìm combo cần cập nhật (eager load 'products' để lấy oldData pivot chính xác)
        $combo = Combo::with('products')->findOrFail($id);
        // Lưu trữ lại dữ liệu cũ trước khi thay đổi để phục vụ việc ghi Log Audit sau này
        $oldData = [
            'combo' => $combo->toArray(),
            'items' => $combo->products->pluck('pivot.quantity', 'id')->toArray()
        ];

        // 1. Xác thực dữ liệu mới gửi lên
        $validated = $request->validate([
            // unique bỏ qua chính ID hiện tại
            'name'        => "required|string|max:150|unique:combos,name,{$id}",
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0|max:99999999.99',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status'      => 'required|in:ACTIVE,INACTIVE',
            'product_ids'   => 'required|array|min:1|max:10',
            'product_ids.*' => 'required|integer|distinct|exists:products,id',
            'quantities'    => 'required|array',
            'quantities.*'  => 'required|integer|min:1|max:100',
        ], [
            'name.required'          => 'Tên combo không được trống.',
            'name.unique'            => 'Tên combo này đã tồn tại. Vui lòng chọn tên khác.',
            'price.required'         => 'Giá combo không được trống.',
            'price.numeric'          => 'Giá combo phải là số.',
            'price.min'              => 'Giá combo không được âm.',
            'status.required'        => 'Trạng thái không được trống.',
            'product_ids.required'   => 'Bạn phải chọn ít nhất một sản phẩm lẻ cho combo.',
            'product_ids.max'        => 'Combo chỉ được phép chứa tối đa 10 sản phẩm lẻ.',
            'product_ids.*.distinct' => 'Bạn đang chọn trùng lặp cùng một sản phẩm lẻ.',
            'product_ids.*.exists'   => 'Một hoặc nhiều sản phẩm lẻ được chọn không hợp lệ.',
            'quantities.*.min'       => 'Số lượng mỗi sản phẩm lẻ trong combo phải tối thiểu là 1.',
            'quantities.*.max'       => 'Số lượng mỗi sản phẩm lẻ không được vượt quá 100.',
        ]);

        // Kiểm tra sản phẩm INACTIVE trong danh sách mới chọn
        $inactiveProducts = Product::whereIn('id', $validated['product_ids'])
            ->where('status', '!=', 'ACTIVE')
            ->pluck('name');

        if ($inactiveProducts->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'product_ids' => 'Các sản phẩm sau không thể thêm vào combo vì không còn hoạt động: ' . $inactiveProducts->join(', ') . '.',
            ]);
        }

         // 2. Xử lý ảnh sản phẩm: Nếu tải lên ảnh mới, tiến hành xóa ảnh cũ trong storage
        $imageUrl = $combo->image_url;
        if ($request->hasFile('image')) {
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            $imageUrl = $request->file('image')->store('combos', 'public');
        }

        // 3. Thực hiện cập nhật trong Transaction
        DB::beginTransaction();
        try {
            // 3.1. Cập nhật thông tin cơ bản của combo
            $combo->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'image_url' => $imageUrl,
                'status' => $validated['status'],
            ]);

           // 3.2. Cập nhật mối quan hệ sản phẩm đi kèm combo trong bảng trung gian `combo_items`
            $syncData = [];
            foreach ($validated['product_ids'] as $productId) {
                $quantity = (int) ($validated['quantities'][$productId] ?? 1);
                if ($quantity > 0) {
                    $syncData[$productId] = ['quantity' => $quantity];
                }
            }
            // Đồng bộ lại mảng sản phẩm lẻ mới (các phần tử cũ không thuộc mảng này sẽ tự động bị xóa)
            if (empty($syncData)) {
                throw new \Exception('Combo phải có ít nhất một sản phẩm với số lượng > 0.');
            }

            $combo->products()->sync($syncData);

            DB::commit();

            // 4. Ghi nhận nhật ký cập nhật kèm cả dữ liệu cũ (old) và mới (new) để so sánh
            AuditLogService::log('COMBO_UPDATE', 'Combo', $combo->id, $oldData, [
                'combo' => $combo->fresh()->toArray(),
                'items' => $syncData
            ]);

            return redirect()->route('admin.combos.index')
                ->with('success', 'Cập nhật combo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // Bước 6: Xóa Combo khỏi hệ thống
    public function destroy($id)
    {
        $combo = Combo::findOrFail($id);

        // 1. KIỂM TRA LOGIC NGHIỆP VỤ: Đảm bảo combo chưa từng phát sinh đơn đặt vé nào (để tránh lỗi toàn vẹn dữ liệu hóa đơn cũ)
        if ($combo->bookingCombos()->count() > 0) {
            return redirect()->route('admin.combos.index')
                ->with('error', 'Không thể xóa combo này vì đã có khách hàng đặt trong hóa đơn.');
        }

        // Lưu thông tin trước khi xóa để đưa vào lịch sử hoạt động
        $oldData = [
            'combo' => $combo->toArray(),
            'items' => $combo->products->pluck('pivot.quantity', 'id')->toArray()
        ];

        DB::beginTransaction();
        try {
            // 2. Xóa ảnh lưu trữ vật lý của combo trên đĩa cứng
            if ($combo->image_url) {
                Storage::disk('public')->delete($combo->image_url);
            }
             // 3. Hủy liên kết (Detach) tất cả các sản phẩm lẻ đi kèm trong bảng `combo_items`
            $combo->products()->detach();
            // 4. Xóa dòng ghi nhận combo trong bảng `combos`
            $combo->delete();

            DB::commit();

             // 5. Ghi nhận lịch sử xóa
            AuditLogService::log('COMBO_DELETE', 'Combo', $id, $oldData, null);

            return redirect()->route('admin.combos.index')
                ->with('success', 'Xóa combo thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.combos.index')
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
