<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerManageController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Banner::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        $banners = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.banner.index', compact('banners'));
    }

    
    public function create()
    {
        return view('admin.banner.create');
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'link_url' => 'nullable|url|max:255',
            'position' => 'required|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ], [
            'image.required' => 'Vui lòng tải lên hình ảnh banner.',
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.max' => 'Kích thước ảnh tối đa là 4MB.',
            'link_url.url' => 'Đường dẫn liên kết (Link URL) không đúng định dạng URL.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('banners', 'public');
        }

        Banner::create([
            'image_url' => $imageUrl,
            'link_url' => $request->link_url,
            'position' => $request->position,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.banners.index')->with('success', 'Thêm mới banner thành công.');
    }

    
    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banner.edit', compact('banner'));
    }

    
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'link_url' => 'nullable|url|max:255',
            'position' => 'required|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:ACTIVE,INACTIVE',
        ], [
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.max' => 'Kích thước ảnh tối đa là 4MB.',
            'link_url.url' => 'Đường dẫn liên kết (Link URL) không đúng định dạng URL.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ]);

        $imageUrl = $banner->image_url;
        if ($request->hasFile('image')) {
            // xóa ảnh cũ
            if ($banner->image_url && Storage::disk('public')->exists($banner->image_url)) {
                Storage::disk('public')->delete($banner->image_url);
            }
            // lưu ảnh mới
            $imageUrl = $request->file('image')->store('banners', 'public');
        }

        $banner->update([
            'image_url' => $imageUrl,
            'link_url' => $request->link_url,
            'position' => $request->position,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.banners.index')->with('success', 'Cập nhật banner thành công.');
    }

    /**
     * Xóa
     */
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image_url && Storage::disk('public')->exists($banner->image_url)) {
            Storage::disk('public')->delete($banner->image_url);
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Xóa banner thành công.');
    }
}
