<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PromotionManageController extends Controller
{
    // Bước 1: Xem danh sách chương trình khuyến mãi (kèm tìm kiếm & lọc trạng thái)
    public function index(Request $request)
    {
        $query = Promotion::query();

        // Tìm kiếm theo tiêu đề hoặc mô tả chiến dịch khuyến mãi
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
            });
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
        $validated = $this->validatePromotion($request);
        // 2. Xử lý tải ảnh banner lên storage
        $bannerUrl = null;
        DB::beginTransaction();
        try {
            $bannerUrl = null;
            if ($request->hasFile('banner')) {
                $bannerUrl = $request->file('banner')->store('promotions', 'public');
            }
            $promotion = Promotion::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'banner_url' => $bannerUrl,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
            ]);
            AuditLogService::log(
                'PROMOTION_CREATE',
                'Promotion',
                $promotion->id,
                null,
                $promotion->toArray()
            );
            DB::commit();
            return redirect()
                ->route('admin.promotions.index')
                ->with('success','Thêm chương trình khuyến mãi mới thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($bannerUrl) {
                Storage::disk('public')->delete($bannerUrl);
            }
            return back()
                ->withInput()
                ->with('error',$e->getMessage());
        }
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
        $validated = $this->validatePromotion($request,$id,$promotion);

        // 2. Xử lý ảnh banner: Xóa banner cũ nếu được thay thế bằng banner mới
        $bannerUrl = $promotion->banner_url;
        DB::beginTransaction();
        $oldBanner = $promotion->banner_url;
        $bannerUrl = $oldBanner;
        try {
            if ($request->hasFile('banner')) { 
                $bannerUrl = $request->file('banner')->store('promotions', 'public');
            }
            $promotion->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'banner_url' => $bannerUrl,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
            ]);

            if ($request->hasFile('banner') && $oldBanner) {
                Storage::disk('public')->delete($oldBanner);
            }

            AuditLogService::log(
                'PROMOTION_UPDATE',
                'Promotion',
                $promotion->id,
                $oldData,
                $promotion->fresh()->toArray()
            );
            DB::commit();
            return redirect()
                ->route('admin.promotions.index')
                ->with('success','Cập nhật chương trình khuyến mãi thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->hasFile('banner') && $bannerUrl && $bannerUrl !== $oldBanner) {
                Storage::disk('public')->delete($bannerUrl);
            }
            return back()
                ->withInput()
                ->with('error','Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    // Bước 6: Xóa chương trình khuyến mãi
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        // Kiểm tra ràng buộc: không cho phép xóa chiến dịch đang ACTIVE
            if ($promotion->start_date->lte(now())) {
                return redirect()->route('admin.promotions.index')
                    ->with('error', 'Không thể xóa chương trình khuyến mãi đã và đang diễn ra.');
            }
        DB::beginTransaction();
        try{
            $oldData = $promotion->toArray();

            $oldBanner = $promotion->banner_url;
            $promotion->delete();
            // 3. Ghi log hành động xóa
            AuditLogService::log('PROMOTION_DELETE', 'Promotion', $id, $oldData, null);
            DB::commit();
            if ($oldBanner) {
                Storage::disk('public')->delete($oldBanner);
            }
            return redirect()->route('admin.promotions.index')
                  ->with('success', 'Xóa chương trình khuyến mãi thành công.');
        }catch(\Exception $e){
            DB::rollBack();
            return redirect()->route('admin.promotions.index')->with('error',$e->getMessage());
        }
    }
    // Phần Validate
    private function validatePromotion(Request $request, $id = null, Promotion $promotion = null)
    {
        $validator = Validator::make($request->all(), [

            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[\pL\pN\s\-\&\+\(\)\/\,\.]+$/u',
                Rule::unique('promotions', 'title')->ignore($id),
            ],

            'description' => 'nullable|string',

            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',

            'start_date' => $id
                ? 'required|date'
                : 'required|date|after_or_equal:today',

            'end_date' => 'required|date|after:start_date',

            'status' => 'required|in:ACTIVE,INACTIVE,EXPIRED',

        ], [

            'title.required' => 'Tiêu đề chương trình không được để trống.',
            'title.unique' => 'Tiêu đề chương trình đã tồn tại.',
            'title.min' => 'Tiêu đề phải có ít nhất 3 ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'title.regex' => 'Tiêu đề chỉ được chứa chữ cái, số, khoảng trắng và các ký tự -, &, +, (), /, dấu phẩy và dấu chấm.',

            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.after_or_equal' => 'Ngày bắt đầu không được ở quá khứ.',

            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',

            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);

        $validator->after(function ($validator) use ($request, $promotion) {

            // Không cho ACTIVE nếu đã hết hạn
            if (
                $request->status === 'ACTIVE'
                && \Carbon\Carbon::parse($request->end_date)->isPast()
            ) {
                $validator->errors()->add(
                    'status',
                    'Không thể kích hoạt chương trình đã hết hạn.'
                );
            }
            // ko cho EXPIRED nếu chưa hết hạn
            if (
                $request->status === 'EXPIRED' &&
                \Carbon\Carbon::parse($request->end_date)
                    ->endOfDay()
                    ->isFuture()
            ) {
                $validator->errors()->add(
                    'status',
                    'Không thể chuyển sang EXPIRED khi chương trình chưa hết hạn.'
                );
            }
            // Không cho ACTIVE trước ngày bắt đầu
            if (
                $request->status === 'ACTIVE' &&
                \Carbon\Carbon::parse($request->start_date)->isFuture()
            ) {
                $validator->errors()->add(
                    'status',
                    'Không thể kích hoạt chương trình khi chưa đến ngày bắt đầu.'
                );
            }
            // Không cho sửa ngày bắt đầu nếu đang ACTIVE
            if (
                $promotion &&
                $promotion->status === 'ACTIVE' &&
                $request->start_date != $promotion->start_date->format('Y-m-d')
            ) {
                $validator->errors()->add(
                    'start_date',
                    'Không thể thay đổi ngày bắt đầu của chương trình đang hoạt động.'
                );
            }
        });

        return $validator->validate();
    }
}
