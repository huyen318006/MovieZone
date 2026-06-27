<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Room;
use App\Models\ShowtimeSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoomManageController extends Controller
{
    /**
     * Danh sách phòng chiếu — lọc trạng thái, tìm kiếm.
     * Hiển thị tất cả phòng chiếu ngay khi vào trang.
     */
    public function index(Request $request)
    {
        $query = Room::query()
            ->withCount('seats')
            ->withCount(['showtimes as upcoming_showtimes_count' => function ($q) {
                $q->where('start_time', '>', now())
                    ->where('status', '!=', 'CANCELLED');
            }]);

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo tên phòng hoặc loại phòng
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('room_type', 'like', "%{$search}%");
            });
        }

        $rooms = $query->orderBy('name')
            ->paginate(10)
            ->appends($request->query());

        // Tính toán thông tin ràng buộc cho từng phòng
        foreach ($rooms as $room) {
            $roomConstraints = $this->getRoomConstraints($room);
            $room->held_seats_count = $roomConstraints['held_seats_count'];
            $room->sold_seats_count = $roomConstraints['sold_seats_count'];
            $room->active_bookings_count = $roomConstraints['active_bookings_count'];
            $room->is_currently_showing = $roomConstraints['is_currently_showing'];
            $room->is_about_to_show = $roomConstraints['is_about_to_show'];
            $room->block_reasons = $roomConstraints['block_reasons'];
            $room->can_hide = empty($roomConstraints['block_reasons']);
            $room->can_edit_important = empty($roomConstraints['block_reasons']);
        }

        return view('admin.room.index', compact('rooms'));
    }

    /**
     * Form thêm phòng chiếu.
     */
    public function create()
    {
        return view('admin.room.create');
    }

    /**
     * Lưu phòng chiếu mới vào database.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRoom($request);
        $validated['cinema_id'] = 1;

        $room = Room::create($validated);

        $this->writeAuditLog('CREATE', $room, null, $room->toArray());

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Thêm phòng chiếu "' . $room->name . '" thành công!');
    }

    /**
     * Form sửa thông tin phòng chiếu.
     */
    public function edit(Room $room)
    {
        $room->loadCount('seats')
            ->loadCount(['showtimes as upcoming_showtimes_count' => function ($q) {
                $q->where('start_time', '>', now())
                    ->where('status', '!=', 'CANCELLED');
            }]);

        // Lấy thông tin ràng buộc chi tiết
        $constraints = $this->getRoomConstraints($room);

        return view('admin.room.edit', compact('room', 'constraints'));
    }

    /**
     * Cập nhật thông tin phòng chiếu.
     */
    public function update(Request $request, Room $room)
    {
        $validated = $this->validateRoom($request, $room);
        $oldValue = $room->toArray();
        $constraints = $this->getRoomConstraints($room);
        $blockReasons = $constraints['block_reasons'];

        // Nếu phòng đang chiếu hoặc sắp chiếu → chặn sửa hoàn toàn
        if ($constraints['is_currently_showing']) {
            return back()
                ->withInput()
                ->with('error', 'Phòng đang có suất chiếu đang diễn ra. Không thể sửa thông tin phòng lúc này.');
        }

        if ($constraints['is_about_to_show']) {
            return back()
                ->withInput()
                ->with('error', 'Phòng có suất chiếu sắp bắt đầu trong 30 phút. Không thể sửa thông tin phòng lúc này.');
        }

        // Nếu thay đổi trường quan trọng → kiểm tra tất cả điều kiện
        if ($this->changesImportantFields($room, $validated) && !empty($blockReasons)) {
            $reasonText = implode('; ', $blockReasons);
            return back()
                ->withInput()
                ->with('error', 'Không thể thay đổi sức chứa hoặc ẩn phòng. Lý do: ' . $reasonText);
        }

        $room->update($validated);

        $this->writeAuditLog('UPDATE', $room, $oldValue, $room->fresh()->toArray());

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Cập nhật phòng chiếu "' . $room->name . '" thành công!');
    }

    /**
     * Ẩn phòng chiếu — chuyển trạng thái sang INACTIVE.
     */
    public function hide(Room $room)
    {
        if ($room->status === 'INACTIVE') {
            return redirect()->route('admin.rooms.index')
                ->with('error', 'Phòng chiếu "' . $room->name . '" đã ở trạng thái ẩn.');
        }

        $constraints = $this->getRoomConstraints($room);
        $blockReasons = $constraints['block_reasons'];

        if (!empty($blockReasons)) {
            $reasonText = implode(' | ', $blockReasons);
            return redirect()->route('admin.rooms.index')
                ->with('error', 'Không thể ẩn phòng "' . $room->name . '". Lý do: ' . $reasonText);
        }

        $oldValue = $room->toArray();
        $room->update(['status' => 'INACTIVE']);

        $this->writeAuditLog('HIDE', $room, $oldValue, $room->fresh()->toArray());

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Đã ẩn phòng chiếu "' . $room->name . '" thành công.');
    }

    /**
     * Khôi phục phòng chiếu — chuyển trạng thái về ACTIVE.
     */
    public function restore(Room $room)
    {
        if ($room->status === 'ACTIVE') {
            return redirect()->route('admin.rooms.index')
                ->with('error', 'Phòng chiếu "' . $room->name . '" đã ở trạng thái hoạt động.');
        }

        $oldValue = $room->toArray();
        $room->update(['status' => 'ACTIVE']);

        $this->writeAuditLog('RESTORE', $room, $oldValue, $room->fresh()->toArray());

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Đã khôi phục phòng chiếu "' . $room->name . '" thành công.');
    }

    /**
     * Xem sơ đồ ghế hiện tại của phòng.
     */
    public function seats(Room $room)
    {
        $room->load(['seats' => function ($q) {
            $q->orderBy('row_label')->orderBy('seat_number');
        }]);

        $seatRows = $room->seats->groupBy('row_label');

        return view('admin.room.seats', compact('room', 'seatRows'));
    }

    /**
     * Lấy tất cả thông tin ràng buộc và lý do chặn của phòng.
     */
    private function getRoomConstraints(Room $room): array
    {
        $isCurrentlyShowing = $room->currentlyShowingShowtimes()->exists();
        $isAboutToShow = $room->aboutToStartShowtimes()->exists();
        $heldSeatsCount = $room->heldSeatsCount();
        $soldSeatsCount = $room->soldSeatsCount();
        $activeBookingsCount = $room->activeBookingsCount();
        $upcomingShowtimesCount = $room->upcomingShowtimes()->count();

        $blockReasons = [];

        if ($isCurrentlyShowing) {
            $blockReasons[] = 'Phòng đang có suất chiếu đang diễn ra';
        }

        if ($isAboutToShow) {
            $blockReasons[] = 'Phòng có suất chiếu sắp bắt đầu trong 30 phút';
        }

        if ($heldSeatsCount > 0) {
            $blockReasons[] = 'Phòng đang có ' . $heldSeatsCount . ' ghế đang được giữ bởi khách hàng';
        }

        if ($soldSeatsCount > 0) {
            $blockReasons[] = 'Phòng đang có ' . $soldSeatsCount . ' vé đã bán cho suất chiếu chưa diễn ra';
        }

        if ($activeBookingsCount > 0) {
            $blockReasons[] = 'Phòng đang có ' . $activeBookingsCount . ' đơn đặt vé chưa hoàn tất';
        }

        if ($upcomingShowtimesCount > 0 && !$isCurrentlyShowing && !$isAboutToShow) {
            $blockReasons[] = 'Phòng đang có ' . $upcomingShowtimesCount . ' suất chiếu chưa diễn ra';
        }

        return [
            'is_currently_showing' => $isCurrentlyShowing,
            'is_about_to_show' => $isAboutToShow,
            'held_seats_count' => $heldSeatsCount,
            'sold_seats_count' => $soldSeatsCount,
            'active_bookings_count' => $activeBookingsCount,
            'upcoming_showtimes_count' => $upcomingShowtimesCount,
            'block_reasons' => $blockReasons,
        ];
    }

    private function validateRoom(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms')->ignore($room?->id),
            ],
            'room_type' => ['required', 'string', 'max:100'],
            'total_seats' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,MAINTENANCE'],
        ], [
            'name.required' => 'Vui lòng nhập tên phòng.',
            'name.unique' => 'Tên phòng đã tồn tại.',
            'room_type.required' => 'Vui lòng nhập loại phòng.',
            'total_seats.required' => 'Vui lòng nhập sức chứa.',
            'total_seats.integer' => 'Sức chứa phải là số nguyên.',
            'total_seats.min' => 'Sức chứa phải lớn hơn 0.',
            'status.required' => 'Vui lòng chọn trạng thái phòng.',
            'status.in' => 'Trạng thái phòng không hợp lệ.',
        ]);
    }

    private function hasUpcomingShowtimes(Room $room): bool
    {
        return $room->showtimes()
            ->where('start_time', '>', now())
            ->where('status', '!=', 'CANCELLED')
            ->exists();
    }

    private function changesImportantFields(Room $room, array $validated): bool
    {
        return (int) $room->total_seats !== (int) $validated['total_seats']
            || ($room->status !== 'INACTIVE' && $validated['status'] === 'INACTIVE');
    }

    private function writeAuditLog(string $action, Room $room, ?array $oldValue, array $newValue): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_name' => 'rooms',
            'entity_id' => (string) $room->id,
            'old_value' => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
            'new_value' => json_encode($newValue, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
