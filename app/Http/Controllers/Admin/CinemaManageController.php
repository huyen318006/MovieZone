<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CinemaManageController extends Controller
{
    /**
     * Danh sách rạp chiếu — phân trang, lọc theo trạng thái, tìm kiếm.
     */
    public function index(Request $request)
    {
        $query = Cinema::withCount('rooms')
            ->withCount(['showtimes as upcoming_showtimes_count' => function ($q) {
                $q->where('start_time', '>', now())
                  ->where('status', '!=', 'CANCELLED');
            }]);

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo tên hoặc thành phố
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $cinemas = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());

        return view('admin.cinema.index', compact('cinemas'));
    }

    /**
     * Form thêm rạp mới.
     */
    public function create()
    {
        return view('admin.cinema.create');
    }

    /**
     * Lưu rạp mới vào database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => [
                'required', 'string', 'max:255',
                // E3: Kiểm tra trùng tên rạp trong cùng thành phố
                Rule::unique('cinemas')->where(function ($query) use ($request) {
                    return $query->where('city', $request->city);
                }),
            ],
            'city'     => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'address'  => 'required|string|max:500',
            'hotline'  => 'nullable|string|max:20',
            'map_url'  => 'nullable|url|max:500',
            'status'   => 'required|in:ACTIVE,INACTIVE',
        ], [
            'name.required'    => 'Vui lòng nhập tên rạp.',
            'name.unique'      => 'Tên rạp đã tồn tại trong cùng thành phố/khu vực.',
            'city.required'    => 'Vui lòng nhập thành phố.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'status.required'  => 'Vui lòng chọn trạng thái.',
            'status.in'        => 'Trạng thái không hợp lệ.',
            'map_url.url'      => 'Đường dẫn Google Map không hợp lệ.',
        ]);

        Cinema::create($validated);

        return redirect()->route('admin.cinemas.index')
            ->with('success', 'Thêm rạp chiếu "' . $validated['name'] . '" thành công!');
    }

    /**
     * Form sửa thông tin rạp.
     */
    public function edit($id)
    {
        $cinema = Cinema::withCount('rooms')
            ->withCount(['showtimes as upcoming_showtimes_count' => function ($q) {
                $q->where('start_time', '>', now())
                  ->where('status', '!=', 'CANCELLED');
            }])
            ->findOrFail($id);

        return view('admin.cinema.edit', compact('cinema'));
    }

    /**
     * Cập nhật thông tin rạp.
     */
    public function update(Request $request, $id)
    {
        $cinema = Cinema::findOrFail($id);

        $validated = $request->validate([
            'name'     => [
                'required', 'string', 'max:255',
                // E3: Kiểm tra trùng tên, bỏ qua bản ghi hiện tại
                Rule::unique('cinemas')->where(function ($query) use ($request) {
                    return $query->where('city', $request->city);
                })->ignore($id),
            ],
            'city'     => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'address'  => 'required|string|max:500',
            'hotline'  => 'nullable|string|max:20',
            'map_url'  => 'nullable|url|max:500',
            'status'   => 'required|in:ACTIVE,INACTIVE',
        ], [
            'name.required'    => 'Vui lòng nhập tên rạp.',
            'name.unique'      => 'Tên rạp đã tồn tại trong cùng thành phố/khu vực.',
            'city.required'    => 'Vui lòng nhập thành phố.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'status.required'  => 'Vui lòng chọn trạng thái.',
            'status.in'        => 'Trạng thái không hợp lệ.',
            'map_url.url'      => 'Đường dẫn Google Map không hợp lệ.',
        ]);

        $cinema->update($validated);

        return redirect()->route('admin.cinemas.index')
            ->with('success', 'Cập nhật rạp "' . $cinema->name . '" thành công!');
    }

    /**
     * A2: Ẩn rạp — Chuyển trạng thái sang INACTIVE.
     * E2: Kiểm tra rạp có suất chiếu sắp diễn ra → hiển thị cảnh báo nhưng vẫn cho ẩn.
     */
    public function hide($id)
    {
        $cinema = Cinema::withCount(['showtimes as upcoming_showtimes_count' => function ($q) {
            $q->where('start_time', '>', now())
              ->where('status', '!=', 'CANCELLED');
        }])->findOrFail($id);

        // Nếu rạp đã ẩn rồi thì không cần thực hiện
        if ($cinema->status === 'INACTIVE') {
            return redirect()->route('admin.cinemas.index')
                ->with('error', 'Rạp "' . $cinema->name . '" đã ở trạng thái ẩn.');
        }

        $cinema->update(['status' => 'INACTIVE']);

        // Thông báo chi tiết nếu có suất chiếu bị ảnh hưởng
        $message = 'Đã ẩn rạp "' . $cinema->name . '" thành công.';
        if ($cinema->upcoming_showtimes_count > 0) {
            $message .= ' Lưu ý: Rạp đang có ' . $cinema->upcoming_showtimes_count . ' suất chiếu sắp diễn ra cần xử lý.';
        }

        return redirect()->route('admin.cinemas.index')
            ->with('success', $message);
    }

    /**
     * A3: Khôi phục rạp — Chuyển trạng thái về ACTIVE.
     */
    public function restore($id)
    {
        $cinema = Cinema::findOrFail($id);

        // Nếu rạp đã hoạt động thì không cần thực hiện
        if ($cinema->status === 'ACTIVE') {
            return redirect()->route('admin.cinemas.index')
                ->with('error', 'Rạp "' . $cinema->name . '" đã ở trạng thái hoạt động.');
        }

        $cinema->update(['status' => 'ACTIVE']);

        return redirect()->route('admin.cinemas.index')
            ->with('success', 'Đã khôi phục rạp "' . $cinema->name . '" thành công.');
    }
}
