<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromotionManageController extends Controller
{
    // Bước 1: Xem danh sách chương trình khuyến mãi (kèm tìm kiếm & lọc trạng thái)
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Tìm kiếm theo tiêu đề hoặc mô tả chiến dịch khuyến mãi
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        // Lọc theo trạng thái khuyến mãi (ACTIVE, INACTIVE, EXPIRED)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // Phân trang
        $promotions = $query->paginate(10)->appends($request->all());

        return view('admin.promotion.index', compact('promotions'));
    }

    // Bước 2: Hiển thị form tạo mới chiến dịch khuyến mãi
    public function create()
    {
        return view('admin.promotion.create');
    }
    // Bước 3: Lưu thông tin khuyến mãi mới
    public function store(Request $request)
    {
        // 1. Xác thực thông tin đầu vào
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096', // Banner tối đa 4MB (ảnh chất lượng cao)
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:ACTIVE,INACTIVE,EXPIRED',
        ], [
            'title.required' => 'Tiêu đề chương trình không được trống.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            // Ngày kết thúc phải sau mốc ngày bắt đầu
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'status.required' => 'Trạng thái không được trống.',
        ]);
        // 2. Xử lý tải ảnh banner lên storage
        $bannerUrl = null;
        if ($request->hasFile('banner')) {
            $bannerUrl = $request->file('banner')->store('promotions', 'public');
        }
        // 3. Tạo mới bản ghi khuyến mãi vào cơ sở dữ liệu
        $promotion = Promotion::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'banner_url' => $bannerUrl,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
        ]);

        // 4. Ghi nhận log tạo mới khuyến mãi
        AuditLogService::log('PROMOTION_CREATE', 'Promotion', $promotion->id, null, $promotion->toArray());

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Thêm chương trình khuyến mãi mới thành công.');
    }

    // Bước 4: Hiển thị giao diện chỉnh sửa khuyến mãi
    public function edit($id)
    {
        $promotion = Promotion::findOrFail($id);
        return view('admin.promotion.edit', compact('promotion'));
    }

    // Bước 5: Cập nhật thông tin khuyến mãi sửa đổi
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);
        // Lưu trữ lại dữ liệu cũ trước khi thực hiện cập nhật
        $oldData = $promotion->toArray();

        // 1. Xác thực thông tin mới gửi lên
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:ACTIVE,INACTIVE,EXPIRED',
        ], [
            'title.required' => 'Tiêu đề chương trình không được trống.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'status.required' => 'Trạng thái không được trống.',
        ]);
        // 2. Xử lý ảnh banner: Xóa banner cũ nếu được thay thế bằng banner mới
        $bannerUrl = $promotion->banner_url;
        if ($request->hasFile('banner')) {
            if ($bannerUrl) {
                Storage::disk('public')->delete($bannerUrl);
            }
            $bannerUrl = $request->file('banner')->store('promotions', 'public');
        }
        // 3. Tiến hành cập nhật thông tin vào DB
        $promotion->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'banner_url' => $bannerUrl,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
        ]);

        // 4. Ghi nhận log chỉnh sửa khuyến mãi
        AuditLogService::log('PROMOTION_UPDATE', 'Promotion', $promotion->id, $oldData, $promotion->fresh()->toArray());

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Cập nhật chương trình khuyến mãi thành công.');
    }

    // Bước 6: Xóa chương trình khuyến mãi
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $oldData = $promotion->toArray();

        // 1. Xóa ảnh banner vật lý trong storage trước khi xóa bản ghi
        if ($promotion->banner_url) {
            Storage::disk('public')->delete($promotion->banner_url);
        }
        // 2. Xóa bản ghi khuyến mãi trong DB
        $promotion->delete();

        // 3. Ghi log hành động xóa
        AuditLogService::log('PROMOTION_DELETE', 'Promotion', $id, $oldData, null);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Xóa chương trình khuyến mãi thành công.');
    }
}
