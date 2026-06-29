<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductManageController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate(10)->appends($request->all());

        return view('admin.product.index', compact('products'));
    }

    public function create()
    {
        return view('admin.product.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required|in:ACTIVE,INACTIVE,OUT_OF_STOCK',
        ], [
            'name.required' => 'Tên sản phẩm không được trống.',
            'price.required' => 'Giá sản phẩm không được trống.',
            'price.numeric' => 'Giá sản phẩm phải là số.',
            'price.min' => 'Giá sản phẩm không được âm.',
            'status.required' => 'Trạng thái không được trống.',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'image_url' => $imageUrl,
            'status' => $validated['status'],
        ]);

        // Audit Log
        AuditLogService::log('PRODUCT_CREATE', 'Product', $product->id, null, $product->toArray());

        return redirect()->route('admin.products.index')
            ->with('success', 'Thêm sản phẩm mới thành công.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.product.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $oldData = $product->toArray();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required|in:ACTIVE,INACTIVE,OUT_OF_STOCK',
        ], [
            'name.required' => 'Tên sản phẩm không được trống.',
            'price.required' => 'Giá sản phẩm không được trống.',
            'price.numeric' => 'Giá sản phẩm phải là số.',
            'price.min' => 'Giá sản phẩm không được âm.',
            'status.required' => 'Trạng thái không được trống.',
        ]);

        $imageUrl = $product->image_url;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($imageUrl) {
                Storage::disk('public')->delete($imageUrl);
            }
            $imageUrl = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'image_url' => $imageUrl,
            'status' => $validated['status'],
        ]);

        // Audit Log
        AuditLogService::log('PRODUCT_UPDATE', 'Product', $product->id, $oldData, $product->fresh()->toArray());

        return redirect()->route('admin.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Check if product is in any combo
        if ($product->combos()->count() > 0) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Không thể xóa sản phẩm này vì nó đang nằm trong một số combo.');
        }

        $oldData = $product->toArray();

        // Delete image
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        // Audit Log
        AuditLogService::log('PRODUCT_DELETE', 'Product', $id, $oldData, null);

        return redirect()->route('admin.products.index')
            ->with('success', 'Xóa sản phẩm thành công.');
    }
}
