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
    public function index(Request $request)
    {
        $query = Combo::with('products');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $combos = $query->paginate(10)->appends($request->all());

        return view('admin.combo.index', compact('combos'));
    }

    public function create()
    {
        // Load all active products to display in the checkbox/quantity list
        $products = Product::where('status', 'ACTIVE')->get();
        return view('admin.combo.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'quantities' => 'required|array',
        ], [
            'name.required' => 'Tên combo không được trống.',
            'price.required' => 'Giá combo không được trống.',
            'price.numeric' => 'Giá combo phải là số.',
            'price.min' => 'Giá combo không được âm.',
            'status.required' => 'Trạng thái không được trống.',
            'product_ids.required' => 'Bạn phải chọn ít nhất một sản phẩm lẻ cho combo.',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('combos', 'public');
        }

        DB::beginTransaction();
        try {
            $combo = Combo::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'image_url' => $imageUrl,
                'status' => $validated['status'],
            ]);

            // Sync combo products
            $syncData = [];
            foreach ($validated['product_ids'] as $productId) {
                $quantity = (int) ($validated['quantities'][$productId] ?? 1);
                if ($quantity > 0) {
                    $syncData[$productId] = ['quantity' => $quantity];
                }
            }
            $combo->products()->sync($syncData);

            DB::commit();

            // Log action
            AuditLogService::log('COMBO_CREATE', 'Combo', $combo->id, null, [
                'combo' => $combo->toArray(),
                'items' => $syncData
            ]);

            return redirect()->route('admin.combos.index')
                ->with('success', 'Thêm combo bắp nước thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $combo = Combo::with('products')->findOrFail($id);
        $products = Product::where('status', 'ACTIVE')->get();
        
        // Map quantity of existing products in the combo
        $selectedProducts = $combo->products->pluck('pivot.quantity', 'id')->toArray();

        return view('admin.combo.edit', compact('combo', 'products', 'selectedProducts'));
    }

    public function update(Request $request, $id)
    {
        $combo = Combo::findOrFail($id);
        $oldData = [
            'combo' => $combo->toArray(),
            'items' => $combo->products->pluck('pivot.quantity', 'id')->toArray()
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'quantities' => 'required|array',
        ], [
            'name.required' => 'Tên combo không được trống.',
            'price.required' => 'Giá combo không được trống.',
            'price.numeric' => 'Giá combo phải là số.',
            'price.min' => 'Giá combo không được âm.',
            'status.required' => 'Trạng thái không được trống.',
            'product_ids.required' => 'Bạn phải chọn ít nhất một sản phẩm lẻ cho combo.',
        ]);

        $imageUrl = $combo->image_url;
        if ($request->hasFile('image')) {
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            $imageUrl = $request->file('image')->store('combos', 'public');
        }

        DB::beginTransaction();
        try {
            $combo->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'image_url' => $imageUrl,
                'status' => $validated['status'],
            ]);

            // Sync combo products
            $syncData = [];
            foreach ($validated['product_ids'] as $productId) {
                $quantity = (int) ($validated['quantities'][$productId] ?? 1);
                if ($quantity > 0) {
                    $syncData[$productId] = ['quantity' => $quantity];
                }
            }
            $combo->products()->sync($syncData);

            DB::commit();

            // Log action
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

    public function destroy($id)
    {
        $combo = Combo::findOrFail($id);

        // Check if combo is in any booking
        if ($combo->bookingCombos()->count() > 0) {
            return redirect()->route('admin.combos.index')
                ->with('error', 'Không thể xóa combo này vì đã có khách hàng đặt trong hóa đơn.');
        }

        $oldData = [
            'combo' => $combo->toArray(),
            'items' => $combo->products->pluck('pivot.quantity', 'id')->toArray()
        ];

        DB::beginTransaction();
        try {
            // Delete image file
            if ($combo->image_url) {
                Storage::disk('public')->delete($combo->image_url);
            }

            // Detach products
            $combo->products()->detach();
            $combo->delete();

            DB::commit();

            // Log action
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
