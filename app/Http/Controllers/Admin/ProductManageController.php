<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductManageController extends Controller
{
    // Bước 1: Hiển thị danh sách sản phẩm lẻ kèm tìm kiếm và lọc trạng thái
    public function index(Request $request)
    {
        // Khởi tạo đối tượng truy vấn Builder của Model Product
        $query = Product::query();
        // Kiểm tra lọc theo từ khóa tìm kiếm (tên hoặc mô tả sản phẩm)
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        // Lọc theo trạng thái sản phẩm (ACTIVE, INACTIVE, OUT_OF_STOCK)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Phân trang 10 dòng/trang, đính kèm query parameters của request cũ vào link phân trang
        $products = $query->paginate(10)->appends($request->all());

        return view('admin.product.index', compact('products'));
    }

    // Bước 2: Hiển thị giao diện thêm sản phẩm lẻ
    public function create()
    {
        return view('admin.product.create');
    }

    // Bước 3: Lưu sản phẩm lẻ mới vào cơ sở dữ liệu
    public function store(Request $request)
    {
        // 1. Xác thực dữ liệu đầu vào
        $validated = $request->validate([
            // Thêm unique để tránh 2 sản phẩm trùng tên gây nhầm lẫn cho admin khi chọn combo
            'name'        => 'required|string|max:150|unique:products,name',
            'description' => 'nullable|string',
            // Giới hạn giá tối đa hợp lý để tránh nhập nhầm
            'price'       => 'required|numeric|min:0|max:999999.99',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status'      => 'required|in:ACTIVE,INACTIVE,OUT_OF_STOCK',
        ], [
            'name.required'   => 'Tên sản phẩm không được trống.',
            'name.unique'     => 'Tên sản phẩm này đã tồn tại. Vui lòng chọn tên khác.',
            'price.required'  => 'Giá sản phẩm không được trống.',
            'price.numeric'   => 'Giá sản phẩm phải là số.',
            'price.min'       => 'Giá sản phẩm không được âm.',
            'price.max'       => 'Giá sản phẩm không được vượt quá 999.999đ. Vui lòng kiểm tra lại.',
            'status.required' => 'Trạng thái không được trống.',
        ]);
        // 2. Xử lý upload ảnh sản phẩm
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('products', 'public');
        }
        // 3. Tạo mới bản ghi sản phẩm lẻ trong DB
        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'image_url' => $imageUrl,
            'status' => $validated['status'],
        ]);

        // 4. Ghi nhận lịch sử hoạt động của Admin (Audit Log)
        AuditLogService::log('PRODUCT_CREATE', 'Product', $product->id, null, $product->toArray());

        return redirect()->route('admin.products.index')
            ->with('success', 'Thêm sản phẩm mới thành công.');
    }

    // Bước 4: Hiển thị giao diện chỉnh sửa sản phẩm
    public function edit($id)
    {
        // Tìm sản phẩm theo ID hoặc tự động trả về lỗi 404 nếu không tồn tại
        $product = Product::withCount(['combos' => function ($q) {
            // Đếm số combo ACTIVE đang chứa sản phẩm này để cảnh báo admin trên view
            $q->where('status', 'ACTIVE');
        }])->findOrFail($id);
        return view('admin.product.edit', compact('product'));
    }

    // Bước 5: Cập nhật thông tin thay đổi của sản phẩm
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        // Lưu trữ lại dữ liệu cũ trước khi cập nhật để ghi nhận vào log lịch sử
        $oldData = $product->toArray();

        // 1. Xác thực dữ liệu sửa đổi gửi lên
        $validated = $request->validate([
            // unique bỏ qua ID hiện tại của chính sản phẩm
            'name'        => "required|string|max:150|unique:products,name,{$id}",
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0|max:999999.99',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status'      => 'required|in:ACTIVE,INACTIVE,OUT_OF_STOCK',
        ], [
            'name.required'  => 'Tên sản phẩm không được trống.',
            'name.unique'    => 'Tên sản phẩm này đã tồn tại.',
            'price.required' => 'Giá sản phẩm không được trống.',
            'price.numeric'  => 'Giá sản phẩm phải là số.',
            'price.min'      => 'Giá sản phẩm không được âm.',
            'price.max'      => 'Giá sản phẩm không được vượt quá 999.999đ.',
            'status.required' => 'Trạng thái không được trống.',
        ]);

        // Cảnh báo nếu admin chuyển sản phẩm về INACTIVE/OUT_OF_STOCK
        // trong khi sản phẩm này đang được dùng trong combo ACTIVE
        $warningMsg       = null;
        $activeComboCount = $product->combos()->where('status', 'ACTIVE')->count();
        $isDeactivating   = in_array($validated['status'], ['INACTIVE', 'OUT_OF_STOCK'])
                           && $product->status === 'ACTIVE';

        if ($isDeactivating && $activeComboCount > 0) {
            $warningMsg = "Cảnh báo: Sản phẩm này đang là thành phần của {$activeComboCount} combo đang ACTIVE. "
                        . 'Thay đổi trạng thái có thể ảnh hưởng đến việc hiển thị combo cho khách hàng.';
        }
        // 2. Xử lý cập nhật ảnh: Xóa ảnh cũ trên disk nếu admin tải lên ảnh mới thế chỗ
        $imageUrl = $product->image_url;
        if ($request->hasFile('image')) {
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            $imageUrl = $request->file('image')->store('products', 'public');
        }
        // 3. Tiến hành cập nhật thông tin sản phẩm vào DB
        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'image_url' => $imageUrl,
            'status' => $validated['status'],
        ]);

        // 4. Ghi nhận log cập nhật (kèm dữ liệu cũ $oldData và dữ liệu mới $product->fresh())
        AuditLogService::log('PRODUCT_UPDATE', 'Product', $product->id, $oldData, $product->fresh()->toArray());

        return redirect()->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.')
            ->with('warning', $warningMsg);
    }

    // Bước 6: Xóa sản phẩm lẻ
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // 1. KIỂM TRA LOGIC RÀNG BUỘC: Đảm bảo sản phẩm lẻ này không nằm trong bất kỳ Combo bắp nước nào
        if ($product->combos()->count() > 0) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Không thể xóa sản phẩm này vì nó đang nằm trong một số combo.');
        }
        // Lưu trữ thông tin sản phẩm trước khi tiến hành xóa
        $oldData = $product->toArray();

        // 2. Xóa tệp tin ảnh lưu trong ổ đĩa cứng (storage)
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        // 3. Xóa bản ghi trong database
        $product->delete();

        // 4. Ghi nhận lịch sử xóa
        AuditLogService::log('PRODUCT_DELETE', 'Product', $id, $oldData, null);

        return redirect()->route('admin.products.index')
            ->with('success', 'Xóa sản phẩm thành công.');
    }
}
