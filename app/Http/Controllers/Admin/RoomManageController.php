<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cinema;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RoomManageController extends Controller
{
    /**
     * Danh sách phòng chiếu — chọn rạp, lọc trạng thái, tìm kiếm.
     */
    public function index(Request $request)
    {
        $cinemas = Cinema::withCount('rooms')
            ->orderBy('name')
            ->get();

        $selectedCinema = null;
        $rooms = collect();

        if ($request->filled('cinema')) {
            $selectedCinema = Cinema::find($request->cinema);

            if ($selectedCinema) {
                $query = Room::with('cinema')
                    ->withCount('seats')
                    ->withCount(['showtimes as upcoming_showtimes_count' => function ($q) {
                        $q->where('start_time', '>', now())
                            ->where('status', '!=', 'CANCELLED');
                    }])
                    ->where('cinema_id', $selectedCinema->id);

                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }

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
            }
        }

        return view('admin.room.index', compact('cinemas', 'selectedCinema', 'rooms'));
    }

    /**
     * Form thêm phòng chiếu.
     */
    public function create(Request $request)
    {
        $cinemas = Cinema::where('status', 'ACTIVE')->orderBy('name')->get();
        $selectedCinema = null;

        if ($request->filled('cinema')) {
            $selectedCinema = Cinema::where('status', 'ACTIVE')->find($request->cinema);
        }

        return view('admin.room.create', compact('cinemas', 'selectedCinema'));
    }

    /**
     * Lưu phòng chiếu mới vào database.
     */
    public function store(Request $request)
    {
        $validated = $this->validateRoom($request);

        $cinema = Cinema::find($validated['cinema_id']);

        if (!$cinema || $cinema->status !== 'ACTIVE') {
            return back()
                ->withInput()
                ->withErrors(['cinema_id' => 'Rạp không tồn tại hoặc đang bị ẩn, không thể thêm phòng chiếu.']);
        }

        $room = Room::create($validated);

        $this->writeAuditLog('CREATE', $room, null, $room->toArray());

        return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
            ->with('success', 'Thêm phòng chiếu "' . $room->name . '" thành công!');
    }

    /**
     * Form sửa thông tin phòng chiếu.
     */
    public function edit(Room $room)
    {
        $room->load('cinema')
            ->loadCount('seats')
            ->loadCount(['showtimes as upcoming_showtimes_count' => function ($q) {
                $q->where('start_time', '>', now())
                    ->where('status', '!=', 'CANCELLED');
            }]);

        $cinemas = Cinema::where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.room.edit', compact('room', 'cinemas'));
    }

    /**
     * Cập nhật thông tin phòng chiếu.
     */
    public function update(Request $request, Room $room)
    {
        $validated = $this->validateRoom($request, $room);
        $oldValue = $room->toArray();
        $hasUpcomingShowtimes = $this->hasUpcomingShowtimes($room);

        if ($hasUpcomingShowtimes && $this->changesImportantFields($room, $validated)) {
            return back()
                ->withInput()
                ->with('error', 'Phòng đang có suất chiếu chưa diễn ra. Vui lòng xử lý suất chiếu liên quan trước khi sửa rạp, sức chứa hoặc ẩn phòng.');
        }

        $cinema = Cinema::find($validated['cinema_id']);

        if (!$cinema || $cinema->status !== 'ACTIVE') {
            return back()
                ->withInput()
                ->withErrors(['cinema_id' => 'Rạp không tồn tại hoặc đang bị ẩn, không thể gán phòng chiếu vào rạp này.']);
        }

        $room->update($validated);

        $this->writeAuditLog('UPDATE', $room, $oldValue, $room->fresh()->toArray());

        return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
            ->with('success', 'Cập nhật phòng chiếu "' . $room->name . '" thành công!');
    }

    /**
     * Ẩn phòng chiếu — chuyển trạng thái sang INACTIVE.
     */
    public function hide(Room $room)
    {
        if ($room->status === 'INACTIVE') {
            return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
                ->with('error', 'Phòng chiếu "' . $room->name . '" đã ở trạng thái ẩn.');
        }

        if ($this->hasUpcomingShowtimes($room)) {
            return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
                ->with('error', 'Phòng "' . $room->name . '" đang có suất chiếu chưa diễn ra. Vui lòng xử lý suất chiếu liên quan trước khi ẩn phòng.');
        }

        $oldValue = $room->toArray();
        $room->update(['status' => 'INACTIVE']);

        $this->writeAuditLog('HIDE', $room, $oldValue, $room->fresh()->toArray());

        return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
            ->with('success', 'Đã ẩn phòng chiếu "' . $room->name . '" thành công.');
    }

    /**
     * Khôi phục phòng chiếu — chuyển trạng thái về ACTIVE.
     */
    public function restore(Room $room)
    {
        if ($room->status === 'ACTIVE') {
            return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
                ->with('error', 'Phòng chiếu "' . $room->name . '" đã ở trạng thái hoạt động.');
        }

        if (!$room->cinema || $room->cinema->status !== 'ACTIVE') {
            return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
                ->with('error', 'Không thể khôi phục phòng vì rạp cha không hoạt động.');
        }

        $oldValue = $room->toArray();
        $room->update(['status' => 'ACTIVE']);

        $this->writeAuditLog('RESTORE', $room, $oldValue, $room->fresh()->toArray());

        return redirect()->route('admin.rooms.index', ['cinema' => $room->cinema_id])
            ->with('success', 'Đã khôi phục phòng chiếu "' . $room->name . '" thành công.');
    }

    /**
     * Xem sơ đồ ghế hiện tại của phòng.
     */
    public function seats(Room $room)
    {
        $room->load(['cinema', 'seats' => function ($q) {
            $q->orderBy('row_label')->orderBy('seat_number');
        }]);

        $seatRows = $room->seats->groupBy('row_label');

        return view('admin.room.seats', compact('room', 'seatRows'));
    }

    private function validateRoom(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'cinema_id' => ['required', 'exists:cinemas,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms')->where(function ($query) use ($request) {
                    return $query->where('cinema_id', $request->cinema_id);
                })->ignore($room?->id),
            ],
            'room_type' => ['required', 'string', 'max:100'],
            'total_seats' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,MAINTENANCE'],
        ], [
            'cinema_id.required' => 'Vui lòng chọn rạp.',
            'cinema_id.exists' => 'Rạp không tồn tại.',
            'name.required' => 'Vui lòng nhập tên phòng.',
            'name.unique' => 'Tên phòng đã tồn tại trong rạp này.',
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
        return (int) $room->cinema_id !== (int) $validated['cinema_id']
            || (int) $room->total_seats !== (int) $validated['total_seats']
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
