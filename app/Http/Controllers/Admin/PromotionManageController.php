<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromotionManageController extends Controller
{
    public function index(Request $request)
    {
        $query = Promotion::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $promotions = $query->paginate(10)->appends($request->all());

        return view('admin.promotion.index', compact('promotions'));
    }

    public function create()
    {
        return view('admin.promotion.create');
    }

    public function store(Request $request)
    {
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

        // Audit Log
        AuditLogService::log('PROMOTION_CREATE', 'Promotion', $promotion->id, null, $promotion->toArray());

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Thêm chương trình khuyến mãi mới thành công.');
    }

    public function edit($id)
    {
        $promotion = Promotion::findOrFail($id);
        return view('admin.promotion.edit', compact('promotion'));
    }

    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);
        $oldData = $promotion->toArray();

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

        $bannerUrl = $promotion->banner_url;
        if ($request->hasFile('banner')) {
            if ($bannerUrl) {
                Storage::disk('public')->delete($bannerUrl);
            }
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

        // Audit Log
        AuditLogService::log('PROMOTION_UPDATE', 'Promotion', $promotion->id, $oldData, $promotion->fresh()->toArray());

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Cập nhật chương trình khuyến mãi thành công.');
    }

    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $oldData = $promotion->toArray();

        if ($promotion->banner_url) {
            Storage::disk('public')->delete($promotion->banner_url);
        }

        $promotion->delete();

        // Audit Log
        AuditLogService::log('PROMOTION_DELETE', 'Promotion', $id, $oldData, null);

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Xóa chương trình khuyến mãi thành công.');
    }
}
