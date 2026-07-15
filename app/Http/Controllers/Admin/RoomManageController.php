<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\TicketPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomManageController extends Controller
{
    // ===== Auto-create seats when creating room =====

    private int $seatsPerRow = 10;

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

        // Auto-create seats theo cấu trúc phòng.
        $this->autoCreateSeatsForRoom($room);

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

        $wasTotalSeatsChanged = (int) $room->total_seats !== (int) ($validated['total_seats'] ?? $room->total_seats);
        $wasStatusChanged = (string) $room->status !== (string) ($validated['status'] ?? $room->status);

        $room->update($validated);

        // Nếu admin sửa sức chứa (total_seats) hoặc chuyển trạng thái, và phòng đang ACTIVE
        // thì rebuild layout seats theo total_seats mới.
        // Điều này đảm bảo ví dụ: chọn 2D -> auto ghế theo total_seats; sau đó sửa lại total_seats -> layout đổi theo.
        if ($room->status === 'ACTIVE' && ($wasTotalSeatsChanged || $wasStatusChanged)) {
            $this->rebuildSeatsForRoom($room);
        }

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
        $validated = $request->validate([
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

        // Validate sức chứa tối đa theo room_type (giá trị capacity lúc chọn trên UI + 10)
        $allowance = 10;
        $roomType = (string) ($validated['room_type'] ?? '');
        $totalSeats = (int) ($validated['total_seats'] ?? 0);

        $maxBaseByRoomType = match ($roomType) {
            '2D' => 120 + $allowance,      // UI: 120
            '3D' => 140 + 0,              // UI: 140 (không cộng allowance theo logic bạn chốt trước đó)
            'IMAX' => 160 + 0,           // UI: 160
            '4DX' => 100 + 0,            // UI: 100
            'Goldclass' => 40 + 0,       // UI: 40
            default => null,
        };

        if ($maxBaseByRoomType !== null && $totalSeats > $maxBaseByRoomType) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'total_seats' => "Sức chứa tối đa cho phòng {$roomType} là {$maxBaseByRoomType} ghế.",
            ]);
        }

        return $validated;
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

    /**
     * Tự động tạo seats khi tạo room mới theo cùng logic computeZones() đang dùng ở SeatManageController.
     */
    private function autoCreateSeatsForRoom(Room $room): void
    {
        // Chỉ auto tạo khi phòng active
        if ($room->status !== 'ACTIVE') {
            return;
        }

        $zones = $this->computeZonesLikeSeatManage($room);

        // Nếu đã có seats thì không tạo lại
        $existingCount = $room->seats()->count();
        if ($existingCount > 0) {
            return;
        }

        $seatsPerRow = $zones['seatsPerRow'];
        $standardRows = $zones['standardRows'];
        $vipRows = $zones['vipRows'];
        $coupleRows = $zones['coupleRows'];

        $cinemaId = (int) $room->cinema_id;
        $roomType = (string) $room->room_type;

        // TicketPriceSeeder đang seed room_type theo 2 giá trị: STANDARD / VIP
        // (không có 2D/3D/IMAX/...). Vì vậy map room_type -> TicketPrice.room_type ở đây.
        $ticketRoomType = match ($roomType) {
            '2D', '3D' => 'STANDARD',
            'IMAX', '4DX', 'Goldclass' => 'VIP',
            default => 'STANDARD',
        };

        // Price theo từng room_type (bạn đang muốn mapping theo số ghế/giá cố định)
        // Theo note của bạn:
        // 2D=120, 3D=140, IMAX=160, VIP=80, 4DX=100
        // => chúng ta dùng total_seats (đã được set default khi chọn room_type ở UI)
        // và giá ghế lấy theo TicketPrice (STANDARD/VIP/COUPLE) như hiện tại.

        $seatPriceByType = [
            // STANDARD/VIP/COUPLE lấy theo ma trận giá TicketPrice hiện tại,
            // nhưng map total_seats theo room_type ở UI (2D/3D/IMAX/4DX/Vip/G...) để ra đúng số ghế.
            // Note của bạn: giá label/room_type (2D=120, 3D=140, IMAX=160, VIP=80, 4DX=100)
            // ở đây được hiểu là tổng số ghế (total_seats) đã set sẵn.
            'STANDARD' => $this->getSeatPriceFromTicketPricesLikeSeatManage('STANDARD', $cinemaId, $ticketRoomType),
            'VIP' => $this->getSeatPriceFromTicketPricesLikeSeatManage('VIP', $cinemaId, $ticketRoomType),
            'COUPLE' => $this->getSeatPriceFromTicketPricesLikeSeatManage('COUPLE', $cinemaId, $ticketRoomType),
        ];

        // Ghi theo transaction
        $targetSeatCount = (int) $room->total_seats;

        DB::transaction(function () use (
            $room,
            $standardRows,
            $vipRows,
            $coupleRows,
            $seatPriceByType,
            $seatsPerRow,
            $targetSeatCount
        ) {
            $created = 0;

            // helper tạo seat và dừng khi đủ total_seats
            $createSeat = function (string $rowLabel, int $i, string $seatType) use (
                $room,
                $seatPriceByType,
                &$created,
                $targetSeatCount,
                $seatsPerRow
            ) {
                if ($created >= $targetSeatCount) {
                    return false;
                }

                $seatCode = $rowLabel . $i;

                \App\Models\Seat::create([
                    'room_id' => $room->id,
                    'row_label' => $rowLabel,
                    'seat_number' => $i,
                    'seat_code' => $seatCode,
                    'seat_type' => $seatType,
                    'status' => 'ACTIVE',
                    'price' => $seatPriceByType[$seatType],
                ]);

                $created++;
                return true;
            };

            foreach ($standardRows as $rowLabel) {
                for ($i = 1; $i <= $seatsPerRow; $i++) {
                    if (! $createSeat($rowLabel, $i, 'STANDARD')) {
                        return;
                    }
                }
            }

            foreach ($vipRows as $rowLabel) {
                for ($i = 1; $i <= $seatsPerRow; $i++) {
                    if (! $createSeat($rowLabel, $i, 'VIP')) {
                        return;
                    }
                }
            }

            foreach ($coupleRows as $rowLabel) {
                for ($i = 1; $i <= $seatsPerRow; $i++) {
                    if (! $createSeat($rowLabel, $i, 'COUPLE')) {
                        return;
                    }
                }
            }
        });

        // Đồng bộ showtime_seats cho các suất chiếu tương lai.
        $this->ensureShowtimeSeatsForRoom($room->id);
    }

    private function computeZonesLikeSeatManage(Room $room): array
    {
        $totalSeats = (int) $room->total_seats;
        $seatsPerRow = $this->seatsPerRow;

        $totalRows = (int) ceil($totalSeats / $seatsPerRow);
        $totalRows = max($totalRows, 3);

        $coupleCount = 1;
        $remainingRows = $totalRows - $coupleCount;

        $vipCount = max(1, (int) round($remainingRows * 0.55));
        $standardCount = $remainingRows - $vipCount;

        $frontStandardCount = (int) ceil($standardCount / 2);
        $backStandardCount = $standardCount - $frontStandardCount;

        $allRowLetters = array_map(
            fn ($n) => chr(64 + $n),
            range(1, $totalRows)
        );

        $frontStandardRows = array_slice($allRowLetters, 0, $frontStandardCount);
        $vipRows = array_slice($allRowLetters, $frontStandardCount, $vipCount);
        $backStandardRows = array_slice($allRowLetters, $frontStandardCount + $vipCount, $backStandardCount);
        $coupleRows = array_slice($allRowLetters, $totalRows - $coupleCount, $coupleCount);

        $standardRows = array_merge($frontStandardRows, $backStandardRows);

        return [
            'totalRows' => $totalRows,
            'seatsPerRow' => $seatsPerRow,
            'maxRow' => end($allRowLetters),
            'standardRows' => $standardRows,
            'vipRows' => $vipRows,
            'coupleRows' => $coupleRows,
        ];
    }

    private function getSeatPriceFromTicketPricesLikeSeatManage(string $seatType, int $cinemaId, string $roomType): float
    {
        $ticketPrice = TicketPrice::query()
            ->where('seat_type', $seatType)
            ->where('room_type', $roomType)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();


        if ($ticketPrice) {
            return (float) $ticketPrice->price;
        }

        // Fallback: ticket_prices có thể không seed theo room_type đúng
        $ticketPriceAny = TicketPrice::query()
            ->where('seat_type', $seatType)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();

        if ($ticketPriceAny) {
            return (float) $ticketPriceAny->price;
        }

        return match ($seatType) {
            'VIP' => 150000.0,
            'COUPLE' => 250000.0,
            default => 80000.0,
        };
    }

    /**
     * Mục tiêu: dùng room_type tương ứng TicketPrice.room_type.
     * Hiện code TicketPrice controller/seed đang không rõ mapping sâu, nên giữ đơn giản.
     */


    private function rebuildSeatsForRoom(Room $room): void
    {
        // Nếu phòng không ACTIVE thì không rebuild
        if ($room->status !== 'ACTIVE') {
            return;
        }

        // Nếu đã có seats thì rebuild lại theo total_seats mới.
        // Lưu ý: Seat sử dụng SoftDeletes nên delete sẽ soft-delete.
        // Sau đó sync lại showtime_seats cho các suất chiếu tương lai.
        $roomId = $room->id;

        // Không cho phép rebuild nếu đang có suất chiếu tương lai đang OPEN (tránh lệch dữ liệu booking/hold)
        $hasRealtime = Showtime::query()
            ->where('room_id', $roomId)
            ->where('start_time', '>', now())
            ->where('status', 'OPEN')
            ->exists();

        if ($hasRealtime) {
            // Bạn có thể đổi thông báo theo policy của hệ thống.
            throw new \Exception('Không thể thay đổi layout ghế khi phòng đã có suất chiếu tương lai đang OPEN.');
        }

        $room->seats()->delete();

        // Tạo lại theo total_seats hiện tại
        $this->autoCreateSeatsForRoom($room);
    }

    private function ensureShowtimeSeatsForRoom(int $roomId): void
    {
        $showtimes = Showtime::query()->where('room_id', $roomId)->get();
        if ($showtimes->isEmpty()) {
            return;
        }

        $seats = Seat::query()->where('room_id', $roomId)->get();

        foreach ($showtimes as $showtime) {
            foreach ($seats as $seat) {
                ShowtimeSeat::query()->updateOrCreate(
                    [
                        'showtime_id' => $showtime->id,
                        'seat_id' => $seat->id,
                    ],
                    [
                        'price' => $seat->price,
                        'status' => ($seat->status === 'LOCKED' || $seat->status === 'BROKEN') ? 'LOCKED' : 'AVAILABLE',
                    ]
                );
            }
        }
    }
}

