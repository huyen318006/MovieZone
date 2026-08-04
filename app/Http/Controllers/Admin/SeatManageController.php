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
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SeatManageController extends Controller
{
    private array $allowedStatuses = ['ACTIVE', 'BLOCKED', 'BROKEN'];

    /**
     * Chặn thao tác ghế khi:
     *  - Suất chiếu đã bắt đầu hoặc sắp bắt đầu trong 30 phút
     *  - Có booking không cancelled (trừ CANCELLED/REFUNDED)
     */
    private function assertSeatRoomNotLockedForRealtime(Room $room): void
    {
        // Suất chiếu đã bắt đầu
        $hasStartedShowtime = $room->showtimes()
            ->where('start_time', '<=', now())
            ->where('status', '!=', 'CANCELLED')
            ->exists();

        if ($hasStartedShowtime) {
            throw new \Exception('Phòng này đang trong thời gian chiếu (đã bắt đầu). Không thể chỉnh sửa ghế.');
        }

        // Suất chiếu sắp bắt đầu trong 30 phút
        $hasAboutToStartShowtime = $room->showtimes()
            ->where('start_time', '>', now())
            ->where('start_time', '<=', now()->addMinutes(30))
            ->where('status', '!=', 'CANCELLED')
            ->exists();

        if ($hasAboutToStartShowtime) {
            throw new \Exception('Phòng có suất chiếu sắp bắt đầu trong 30 phút. Không thể chỉnh sửa ghế lúc này.');
        }

        // Có booking không cancelled
        $hasNonCancelledBooking = Booking::query()
            ->whereIn('showtime_id', $room->showtimes()->pluck('id'))
            ->whereNotIn('status', ['CANCELLED', 'REFUNDED'])
            ->exists();

        if ($hasNonCancelledBooking) {
            throw new \Exception('Phòng này đã có khách đặt vé. Không thể chỉnh sửa ghế.');
        }
    }

    private function assertSeatNotLockedForRealtime(Seat $seat): void
    {
        $room = $seat->room;
        if ($room) {
            $this->assertSeatRoomNotLockedForRealtime($room);
        }
    }

    /**
     * Chặn thao tác trên ghế nếu ghế đang HELD (khách đang giữ) hoặc SOLD (đã bán) 
     * trong bất kỳ suất chiếu tương lai nào.
     */
    private function assertSeatNotUsed($seat): void
    {
        if (! $seat instanceof Seat) {
            throw new \InvalidArgumentException('Ghế không hợp lệ.');
        }

        // Kiểm tra trong showtime_seats của suất tương lai có status SOLD/HELD không
        $hasActiveUsage = $seat->showtimeSeats()
            ->whereHas('showtime', function ($q) {
                $q->where('start_time', '>', now())
                  ->where('status', '!=', 'CANCELLED');
            })
            ->whereIn('status', ['SOLD', 'HELD'])
            ->exists();

        if ($hasActiveUsage) {
            throw new \Exception("Ghế {$seat->seat_code} đang được khách đặt/giữ cho suất chiếu sắp tới. Không thể thao tác.");
        }

        // Kiểm tra cache hold realtime (khách đang chọn ghế ngoài frontend)
        $heldBySomeone = $seat->showtimeSeats()
            ->whereHas('showtime', function ($q) {
                $q->where('start_time', '>', now())
                  ->where('status', '!=', 'CANCELLED');
            })
            ->get()
            ->contains(function ($showtimeSeat) {
                $heldBy = Cache::get(
                    'seat_held_'.$showtimeSeat->showtime_id.'_'.$showtimeSeat->id
                );
                return $heldBy && $heldBy != Auth::id();
            });

        if ($heldBySomeone) {
            throw new \Exception("Ghế {$seat->seat_code} đang được khách giữ (hold) trên hệ thống. Không thể thao tác.");
        }
    }

    /**
     * Lấy giá ghế theo seat_type từ bảng ticket_prices.
     * Do hiện hệ thống chưa truyền day_type/time_type nên lấy bản ghi ACTIVE đầu tiên matching cinema + seat_type.
     */
    private function getSeatPriceFromTicketPrices(string $seatType, int $cinemaId): float
    {
        // Ưu tiên match theo cinema_id của rạp/phòng
        $ticketPrice = TicketPrice::query()
            ->where('seat_type', $seatType)
            ->where('cinema_id', $cinemaId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();

        if ($ticketPrice) {
            return (float) $ticketPrice->price;
        }

        // Fallback: nếu hệ thống seed/record chưa khớp cinema_id, lấy record ACTIVE bất kỳ theo seat_type
        $ticketPriceAnyCinema = TicketPrice::query()
            ->where('seat_type', $seatType)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();

        if ($ticketPriceAnyCinema) {
            return (float) $ticketPriceAnyCinema->price;
        }

        // Fallback cuối cùng (chỉ xảy ra khi ticket_prices trống)
        return match ($seatType) {
            'VIP' => 150000.0,
            'COUPLE' => 250000.0,
            default => 80000.0,
        };
    }

    private function ensureAdminAccess(): void
    {
        $user = Auth::user();
        $hasPermission = $user
            && UserRole::where('user_id', $user->id)
                ->whereIn('role_id', [1, 2])
                ->exists();

        if (! $hasPermission) {
            abort(403, 'Bạn không có quyền quản lý ghế.');
        }
    }

    /**
     * Tính toán động các vùng ghế dựa trên total_seats của phòng.
     *
     * Quy tắc:
     *  - Mỗi hàng = seatsPerRow ghế (mặc định 10, cố định theo thiết kế rạp).
     *  - Tổng số hàng = ceil(total_seats / seatsPerRow).
     *  - STANDARD: phần còn lại ở đầu (ít nhất 1 hàng, bắt đầu từ A).
     *  - VIP:      ~55% hàng giữa (nhiều hơn STANDARD, tối thiểu 1 hàng).
     *  - COUPLE:   cố định 1 hàng cuối.
     *
     * @return array{
     *   totalRows: int,
     *   seatsPerRow: int,
     *   maxRow: string,
     *   standardRows: string[],
     *   vipRows: string[],
     *   coupleRows: string[],
     * }
     */
    private function computeZones(Room $room): array
    {
        $totalSeats = (int) $room->total_seats;
        $seatsPerRow = 10; // ghế mỗi hàng (cố định theo thiết kế rạp)

        $totalRows = (int) ceil($totalSeats / $seatsPerRow);
        $totalRows = max($totalRows, 3); // Tối thiểu 3 hàng (1 STANDARD + 1 VIP + 1 COUPLE)

        // COUPLE cố định 1 hàng cuối
        $coupleCount = 1;
        $remainingRows = $totalRows - $coupleCount;

        // VIP chiếm khoảng 55% số hàng còn lại và nằm ở giữa
        $vipCount = max(1, (int) round($remainingRows * 0.55));
        $standardCount = $remainingRows - $vipCount;

        // Mặc định cố gắng đẩy 4 hàng đầu làm STANDARD (theo chuẩn rạp chiếu thường thấy)
        // Nếu số lượng STANDARD quá lớn (>8 hàng), ta chia đôi.
        if ($standardCount <= 8) {
            $frontStandardCount = min($standardCount, 4);
        } else {
            $frontStandardCount = (int) ceil($standardCount / 2);
        }
        $backStandardCount = $standardCount - $frontStandardCount;

        

        // Gán chữ cái hàng
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

    private function normalizeSeatStatus(string $status): string
    {
        return $status === 'LOCKED' ? 'BLOCKED' : $status;
    }

    private function runtimeSeatStatusFromBase(Seat $seat): string
    {
        return in_array($seat->status, ['BLOCKED', 'BROKEN']) ? 'BLOCKED' : 'AVAILABLE';
    }

    private function syncShowtimeSeatState(Seat $seat): void
    {
        $showtimeSeats = ShowtimeSeat::query()
            ->where('seat_id', $seat->id)
            ->whereHas('showtime', function ($query) {
                $query->where('start_time', '>', now());
            })
            ->get();

        foreach ($showtimeSeats as $showtimeSeat) {
            if (! in_array($showtimeSeat->status, ['HELD', 'SOLD'])) {
                // Thay vì gọi hàm phụ, hãy xử lý trực tiếp ở đây cho chắc chắn
                $newStatus = in_array($seat->status, ['BLOCKED', 'BROKEN']) ? 'BLOCKED' : 'AVAILABLE';
                $showtimeSeat->update(['status' => $newStatus]);
            }
        }
    }

    private function ensureShowtimeSeatsForRoom(int $roomId): void
    {
        // 1. Lấy tất cả suất chiếu của phòng này
        $showtimes = Showtime::query()
            ->where('room_id', $roomId)
            ->get();

        if ($showtimes->isEmpty()) {
            return;
        }

        // 2. Lấy tất cả ghế trong phòng kèm theo giá của chúng
        $seats = Seat::query()
            ->where('room_id', $roomId)
            ->get();

        foreach ($showtimes as $showtime) {
            foreach ($seats as $seat) {
                // Dùng updateOrCreate để cập nhật cả giá nếu Admin thay đổi giá ghế
                ShowtimeSeat::query()->updateOrCreate(
                    [
                        'showtime_id' => $showtime->id,
                        'seat_id' => $seat->id,
                    ],
                    [
                        'price' => $seat->price, // Lấy giá từ bảng Seat gốc
                        'status' => ($seat->status === 'LOCKED' || $seat->status === 'BROKEN') ? 'LOCKED' : 'AVAILABLE',
                    ]
                );
            }
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdminAccess();

        $rooms = Room::query()->orderBy('name')->get();
        $selectedRoom = $request->filled('room_id') ? (int) $request->room_id : null;

        $seatsGrouped = [];
        $showtimes = collect();
        $selectedShowtime = null;

        if ($selectedRoom) {
            $seats = Seat::query()
                ->where('room_id', $selectedRoom)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get();

            // Lấy danh sách suất chiếu sắp tới
            $showtimes = Showtime::with('movie')
                ->where('room_id', $selectedRoom)
                ->where('start_time', '>', now())
                ->where('status', '!=', 'CANCELLED')
                ->orderBy('start_time')
                ->get();

// Xác định suất chiếu được chọn
            // Chỉ set selectedShowtime khi người dùng CHỦ ĐỘNG chọn 1 suất từ dropdown
            // Nếu không có param showtime_id hoặc showtime_id rỗng → trạng thái tĩnh (không auto-select)
            $selectedShowtime = null;
            if ($request->has('showtime_id') && $request->filled('showtime_id')) {
                $selectedShowtime = $showtimes->firstWhere('id', (int) $request->showtime_id);
            }

            if ($selectedShowtime instanceof Showtime) {
                $selectedShowtime = $selectedShowtime->load(['showtimeSeats' => function ($q) {
                    $q->with('seat');
                }]);
            } else {
                $selectedShowtime = null;
            }

            // Map dynamic status nếu có suất chiếu
            $dynamicStatuses = []; // seat_id => status
            if ($selectedShowtime instanceof Showtime instanceof Showtime) {
                $showtimeSeats = $selectedShowtime->showtimeSeats;
                $showtimeSeatsBySeatId = $showtimeSeats->keyBy('seat_id');

                // Lấy danh sách showtime_seat_id đã bán
                $soldShowtimeSeatIds = DB::table('booking_seats')
                    ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
                    ->where('bookings.showtime_id', $selectedShowtime->id)
                    ->whereIn('bookings.status', ['PAID', 'PENDING_PAYMENT', 'PENDING_CASH_PAYMENT'])
                    ->pluck('booking_seats.showtime_seat_id');

                // Lấy danh sách showtime_seat_id đang được giữ (từ cache)
                $heldShowtimeSeatIds = $showtimeSeats->filter(function ($stSeat) use ($selectedShowtime) {
                    $cacheKey = 'seat_held_'.$selectedShowtime->id.'_'.$stSeat->id;
                    return Cache::has($cacheKey);
                })->pluck('id');

                foreach ($seats as $seat) {
                    $showtimeSeat = $showtimeSeatsBySeatId->get($seat->id);
                    if ($showtimeSeat) {
                        $baseStatus = $seat->status ?? 'ACTIVE';

                        if (in_array($baseStatus, ['BLOCKED', 'BROKEN'])) {
                            $dynamicStatuses[$seat->id] = $baseStatus;
                        } elseif ($soldShowtimeSeatIds->contains($showtimeSeat->id)) {
                            $dynamicStatuses[$seat->id] = 'SOLD';
                        } elseif ($heldShowtimeSeatIds->contains($showtimeSeat->id)) {
                            $dynamicStatuses[$seat->id] = 'HELD';
                        } else {
                            $dynamicStatuses[$seat->id] = $showtimeSeat->status ?? 'AVAILABLE';
                        }
                    } else {
                        $dynamicStatuses[$seat->id] = 'NOT_SYNCED';
                    }
                }
            }

            // Gán dynamic_status vào mỗi seat
            foreach ($seats as $seat) {
                $seat->dynamic_status = $dynamicStatuses[$seat->id] ?? null;
            }

            $seatsGrouped = $seats->groupBy('row_label');
        }

        return view('admin.seats.index', compact(
            'rooms',
            'seatsGrouped',
            'selectedRoom',
            'showtimes',
            'selectedShowtime'
        ));
    }

    public function create(Request $request)
    {
        $this->ensureAdminAccess();

        $room = Room::with('cinema')->findOrFail($request->room_id);

        if ($room->status !== 'ACTIVE') {
            return redirect()->route('admin.seats.index')
                ->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.']);
        }

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatRoomNotLockedForRealtime($room);
        } catch (\Exception $e) {
            return redirect()->route('admin.seats.index', ['room_id' => $room->id])
                ->withErrors(['error' => $e->getMessage()]);
        }

        return view('admin.seats.create', compact('room'));
    }

    public function edit($id)
    {
        $this->ensureAdminAccess();

        $seat = Seat::with(['room.cinema'])->findOrFail($id);

        if ($seat->room->status !== 'ACTIVE') {
            return redirect()->route('admin.seats.index', [
                'room_id' => $seat->room_id,
            ])->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.']);
        }

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatNotLockedForRealtime($seat);
        } catch (\Exception $e) {
            return redirect()->route('admin.seats.index', ['room_id' => $seat->room_id])
                ->withErrors(['error' => $e->getMessage()]);
        }

        return view('admin.seats.edit', compact('seat'));
    }

    public function store(Request $request)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'row_label' => 'required_unless:seat_type,DEMO|string|max:10',
            'seat_number' => 'required_unless:seat_type,DEMO|integer|min:1',
            'seat_type' => 'required|in:STANDARD,VIP,COUPLE,DEMO',
            'status' => 'required|in:'.implode(',', $this->allowedStatuses),
        ]);

        $room = Room::with('showtimes')->findOrFail($validated['room_id']);

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatRoomNotLockedForRealtime($room);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        $rowLabel = strtoupper($validated['row_label']);
        $isDemo = $validated['seat_type'] === 'DEMO';

        // Ghế DEMO: tự động gán hàng Z, số 99, giá 10.000 VND
        if ($isDemo) {
            $rowLabel = 'Z';
            $validated['row_label'] = 'Z';
            $validated['seat_number'] = 99;
        } else {
            $rowLabel = strtoupper($validated['row_label']);
        }

        // Lấy thông tin phòng để tính vùng ghế động
        $storeRoom = Room::find($validated['room_id']);
        if (! $storeRoom) {
            return back()->withErrors(['error' => 'Phòng không tồn tại.'])->withInput();
        }

        // Bỏ qua validate vùng ghế cho DEMO
        if (! $isDemo) {
            $zones = $this->computeZones($storeRoom);

            if ($validated['seat_type'] === 'VIP' && ! in_array($rowLabel, $zones['vipRows'])) {
                $vipRange = implode(', ', $zones['vipRows']);

                return back()
                    ->withErrors(['error' => "VIP chỉ được đặt ở hàng: {$vipRange}"])
                    ->withInput();
            }

            if ($validated['seat_type'] === 'COUPLE' && ! in_array($rowLabel, $zones['coupleRows'])) {
                $coupleRange = implode(', ', $zones['coupleRows']);

                return back()
                    ->withErrors(['error' => "COUPLE chỉ được đặt ở hàng: {$coupleRange}"])
                    ->withInput();
            }

            if (
                $validated['seat_type'] === 'STANDARD' &&
                (in_array($rowLabel, $zones['vipRows']) || in_array($rowLabel, $zones['coupleRows']))
            ) {
                return back()
                    ->withErrors(['error' => "Hàng {$rowLabel} không hợp lệ cho STANDARD (đây là vùng VIP hoặc COUPLE)"])
                    ->withInput();
            }
        }

        $seatsToCreate = [];
        if ($validated['seat_type'] === 'COUPLE') {
            $num = (int) $validated['seat_number'];
            $num1 = $num % 2 === 1 ? $num : $num - 1;
            $num2 = $num1 + 1;
            $seatsToCreate[] = ['number' => $num1, 'code' => $rowLabel.$num1];
            $seatsToCreate[] = ['number' => $num2, 'code' => $rowLabel.$num2];
        } else {
            $seatsToCreate[] = ['number' => $validated['seat_number'], 'code' => $rowLabel.$validated['seat_number']];
        }

        // Check tồn tại
        foreach ($seatsToCreate as $stc) {
            $exists = Seat::withTrashed()
                ->where('room_id', $validated['room_id'])
                ->where('row_label', $rowLabel)
                ->where('seat_number', $stc['number'])
                ->first();

            if ($exists) {
                // Ghế demo bị xóa mềm → phục hồi
                if ($isDemo && $exists->trashed()) {
                    $exists->restore();
                    $exists->update([
                        'seat_type' => 'DEMO',
                        'price' => 10000,
                        'status' => 'ACTIVE',
                    ]);
                    $this->ensureShowtimeSeatsForRoom($validated['room_id']);

                    return redirect()->route('admin.seats.index', [
                        'room_id' => $validated['room_id'],
                    ])->with('success', 'Đã phục hồi ghế demo Z99 (10.000 VND).');
                }

                return back()
                    ->withErrors(['error' => "Ghế {$stc['code']} đã tồn tại. Không thể tạo."])
                    ->withInput();
            }
        }

        // Giá ghế: DEMO = 10.000 VND cố định, còn lại tra bảng ticket_prices
        $seatPrice = $isDemo ? 10000 : $this->getSeatPriceFromTicketPrices($validated['seat_type'], $validated['room_id']);

        try {
            DB::transaction(function () use ($validated, $rowLabel, $seatsToCreate, $seatPrice) {
                $lastSeat = null;
                foreach ($seatsToCreate as $stc) {
                    $lastSeat = Seat::create(array_merge($validated, [
                        'row_label' => $rowLabel,
                        'seat_number' => $stc['number'],
                        'seat_code' => $stc['code'],
                        'price' => $seatPrice,
                    ]));
                }

                if ($lastSeat) {
                    $this->ensureShowtimeSeatsForRoom($lastSeat->room_id);
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['error' => 'Không thể lưu ghế. Vui lòng kiểm tra lại.'])
                ->withInput();
        }

        $createdCodes = implode(', ', array_column($seatsToCreate, 'code'));
        $successMsg = $isDemo
            ? "✅ Đã tạo ghế demo {$createdCodes} (10.000 VND) thành công."
            : "Thêm ghế {$createdCodes} thành công.";

        return redirect()->route('admin.seats.index', [
            'room_id' => $validated['room_id'],
        ])->with('success', $successMsg);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdminAccess();

        $seat = Seat::withTrashed()->find($id);

        if (! $seat) {
            return back()->withErrors(['error' => 'Không tìm thấy ghế này.']);
        }

        if ($seat->trashed()) {
            return back()->withErrors(['error' => 'Ghế này đã bị xóa mềm trước đó.']);
        }

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatNotLockedForRealtime($seat);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        // Chặn khi ghế đang được khách đặt/giữ (SOLD/HELD)
        try {
            $this->assertSeatNotUsed($seat);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $validated = $request->validate([
            'row_label' => 'required|string|max:10',
            'seat_number' => 'required|integer|min:1',
            'seat_type' => 'required|in:STANDARD,VIP,COUPLE,DEMO',
            // Allow LOCKED on form input; controller sẽ normalize về BLOCKED
            'status' => 'required|in:ACTIVE,LOCKED,BLOCKED,BROKEN',
        ]);

        // Validate zone động giống store/storeBatch (bỏ qua DEMO)
        if ($validated['seat_type'] !== 'DEMO') {
            $room = $seat->room;
            if ($room) {
                $zones = $this->computeZones($room);
                $rowLabelUpper = strtoupper($validated['row_label']);

                if (ord($rowLabelUpper) > ord($zones['maxRow'])) {
                    $vipRange = implode(', ', $zones['vipRows']);
                    $coupleRange = implode(', ', $zones['coupleRows']);
                    return back()
                        ->withErrors(['error' => "Phòng {$room->name} chỉ có tối đa đến hàng {$zones['maxRow']}. "
                                     ."Phân vùng: VIP [{$vipRange}], COUPLE [{$coupleRange}], còn lại là STANDARD."])
                        ->withInput();
                }

                if (in_array($rowLabelUpper, $zones['vipRows'])) {
                    $expectedType = 'VIP';
                } elseif (in_array($rowLabelUpper, $zones['coupleRows'])) {
                    $expectedType = 'COUPLE';
                } else {
                    $expectedType = 'STANDARD';
                }

                if ($validated['seat_type'] !== $expectedType) {
                    $vipRange = implode(', ', $zones['vipRows']);
                    $coupleRange = implode(', ', $zones['coupleRows']);
                    return back()
                        ->withErrors(['error' => "Hàng {$rowLabelUpper} chỉ được phép tạo ghế loại '{$expectedType}'. "
                                     ."(VIP: [{$vipRange}] | COUPLE: [{$coupleRange}] | còn lại: STANDARD)"])
                        ->withInput();
                }
            }
        }

        // Chuẩn hóa: database dùng BLOCKED, form có thể gửi LOCKED
        if ($validated['status'] === 'LOCKED') {
            $validated['status'] = 'BLOCKED';
        }

        $room = $seat->room;
        if (! $room || $room->status !== 'ACTIVE') {
            return back()
                ->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.'])
                ->withInput();
        }

        $rowLabel = strtoupper($validated['row_label']);

        $seatCode = $rowLabel.$validated['seat_number'];

        $isDuplicate = Seat::query()
            ->where('room_id', $seat->room_id)
            ->where('row_label', $rowLabel)
            ->where('seat_number', $validated['seat_number'])
            ->where('id', '!=', $id)
            ->exists();

        if ($isDuplicate) {
            return back()
                ->withErrors(['error' => "Mã ghế {$seatCode} bị trùng trong phòng này."])
                ->withInput();
        }

        $oldData = $seat->only(['row_label', 'seat_number', 'seat_type', 'status', 'price']);
        $newData = array_merge($validated, [
            'row_label' => $rowLabel,
            'seat_code' => $seatCode,
            'price' => $this->getSeatPriceFromTicketPrices($validated['seat_type'], $seat->room_id),
        ]);

        try {
            DB::transaction(function () use ($seat, $newData) {
                $seat->update($newData);
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['error' => 'Không thể cập nhật ghế. Vui lòng thử lại sau.'])
                ->withInput();
        }

        $this->syncShowtimeSeatState($seat->fresh());
        $this->writeAuditLog('seat.update', $seat, $oldData, $newData);

        return redirect()->route('admin.seats.index', [
            'room_id' => $seat->room_id,
        ])->with('success', "Cập nhật ghế {$seatCode} thành công.");
    }

    public function storeBatch(Request $request)
    {
        $this->ensureAdminAccess();

        // ── Bước 1: Validate cơ bản ────────────────────────────────────────────
        $validated = $request->validate(
            [
                'room_id' => 'required|exists:rooms,id',
                'row_label' => [
                    'required',
                    'string',
                    'max:1',
                    'regex:/^[A-Z]$/',
                ],
                'start' => 'required|integer|min:1',
                'end' => 'required|integer|min:1|gte:start',
                'seat_type' => 'required|in:STANDARD,VIP,COUPLE,DEMO',
            ],
            [
                'row_label.required' => 'Vui lòng nhập hàng ghế.',
                'row_label.string' => 'Hàng ghế phải là chữ cái A-Z.',
                'row_label.max' => 'Hàng ghế chỉ 1 ký tự.',
                'row_label.regex' => 'Hàng ghế chỉ được là chữ cái A-Z (không số, không ký tự đặc biệt).',
                'start.min' => 'Số ghế bắt đầu phải lớn hơn 0.',
                'end.min' => 'Số ghế kết thúc phải lớn hơn 0.',
                'end.gte' => 'Số ghế kết thúc phải lớn hơn hoặc bằng số ghế bắt đầu.',
            ]
        );

        // ── Bước 2: Kiểm tra phòng ────────────────────────────────────────────
        $room = Room::find($validated['room_id']);

        if (! $room || $room->status !== 'ACTIVE') {
            return back()
                ->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.'])
                ->withInput();
        }

        $rowLabel = strtoupper($validated['row_label']);

        // ── Bước 3: Tính vùng ghế động từ total_seats ────────────────────────
        $zones = $this->computeZones($room);

        /*
        |-------------------------------------------------------------------
        | Vùng ghế được tính tự động theo total_seats:
        |   STANDARD: ~50% hàng đầu (bắt đầu từ A)
        |   VIP:      ~30% hàng giữa
        |   COUPLE:   ~20% hàng cuối
        |-------------------------------------------------------------------
        */

        // ── Bước 4 & 5: Validate vùng ghế (bỏ qua cho DEMO) ──────────────────
        if ($validated['seat_type'] !== 'DEMO') {
            // Bước 4: Validate hàng ghế không vượt quá phòng
            if (ord($rowLabel) > ord($zones['maxRow'])) {
                $vipRange = implode(', ', $zones['vipRows']);
                $coupleRange = implode(', ', $zones['coupleRows']);

                return back()
                    ->withErrors([
                        'error' => "Phòng {$room->name} ({$room->room_type}) chỉ có {$room->total_seats} ghế, "
                                 ."tối đa đến hàng {$zones['maxRow']}. "
                                 ."Phân vùng: VIP [{$vipRange}], COUPLE [{$coupleRange}], còn lại là STANDARD.",
                    ])
                    ->withInput();
            }

            // Bước 5: Validate chéo row_label ↔ seat_type
            if (in_array($rowLabel, $zones['vipRows'])) {
                $expectedType = 'VIP';
            } elseif (in_array($rowLabel, $zones['coupleRows'])) {
                $expectedType = 'COUPLE';
            } else {
                $expectedType = 'STANDARD';
            }

            if ($validated['seat_type'] !== $expectedType) {
                $vipRange = implode(', ', $zones['vipRows']);
                $coupleRange = implode(', ', $zones['coupleRows']);

                return back()->withErrors([
                    'error' => "Hàng {$rowLabel} chỉ được phép tạo ghế loại '{$expectedType}'. "
                             ."(VIP: [{$vipRange}] | COUPLE: [{$coupleRange}] | còn lại: STANDARD)",
                ])->withInput();
            }
        }

        // ── Bước 6: Validate số ghế không vượt quá seatsPerRow ───────────────
        $seatsPerRow = $zones['seatsPerRow'];
        if ($validated['end'] > $seatsPerRow) {
            return back()
                ->withErrors([
                    'error' => "Số ghế cuối không được vượt quá {$seatsPerRow} ghế/hàng.",
                ])
                ->withInput();
        }

        // ── Bước 7: Giới hạn số ghế tạo mỗi lần ─────────────────────────────
        $batchSize = $validated['end'] - $validated['start'] + 1;
        if ($batchSize > $seatsPerRow) {
            return back()
                ->withErrors([
                    'error' => "Chỉ được tạo tối đa {$seatsPerRow} ghế/lần (1 hàng đầy).",
                ])
                ->withInput();
        }

        // ── Bước 8: Tạo ghế trong transaction ────────────────────────────────
        $created = [];
        $skipped = [];

        try {
            DB::transaction(function () use ($validated, $rowLabel, &$created, &$skipped) {
                for ($i = $validated['start']; $i <= $validated['end']; $i++) {
                    $seatCode = $rowLabel.$i;

                    // Nếu đã tồn tại (kể cả soft-deleted) → bỏ qua hoặc phục hồi
                    $seat = Seat::withTrashed()
                        ->where('room_id', $validated['room_id'])
                        ->where('row_label', $rowLabel)
                        ->where('seat_number', $i)
                        ->first();

                    if ($seat) {
                        $skipped[] = $seatCode;

                        // Phục hồi nếu bị soft-delete
                        if ($seat->trashed()) {
                            $seat->restore();
                            $demoPrice = $validated['seat_type'] === 'DEMO' ? 10000 : $this->getSeatPriceFromTicketPrices(
                                $validated['seat_type'],
                                $validated['room_id']
                            );
                            $seat->update([
                                'seat_type' => $validated['seat_type'],
                                'price' => $demoPrice,
                                'status' => 'ACTIVE',
                            ]);
                            $created[] = $seat->seat_code;
                        }

                        continue;
                    }

                    $batchPrice = $validated['seat_type'] === 'DEMO' ? 10000 : $this->getSeatPriceFromTicketPrices(
                        $validated['seat_type'],
                        $validated['room_id']
                    );
                    $seat = Seat::create([
                        'room_id' => $validated['room_id'],
                        'row_label' => $rowLabel,
                        'seat_number' => $i,
                        'seat_code' => $seatCode,
                        'seat_type' => $validated['seat_type'],
                        'price' => $batchPrice,
                        'status' => 'ACTIVE',
                    ]);

                    $created[] = $seat->seat_code;
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['error' => 'Không thể tạo nhiều ghế. Lỗi: '.$e->getMessage()])
                ->withInput();
        }

        // ── Bước 9: Đồng bộ showtime_seats ───────────────────────────────────
        $this->ensureShowtimeSeatsForRoom($validated['room_id']);

        // ── Bước 10: Trả về kết quả ───────────────────────────────────────────
        $room = Room::findOrFail($validated['room_id']);

        if (! empty($created)) {
            $successMessage = 'Đã tạo '.count($created).' ghế cho hàng '.$rowLabel
                            .': '.implode(', ', $created).'.';
        } else {
            $successMessage = 'Không tạo thêm ghế nào (các ghế đã tồn tại).';
        }

        if (! empty($skipped)) {
            $successMessage .= ' Bỏ qua (đã tồn tại): '.implode(', ', $skipped).'.';
        }

        return redirect()->route('admin.seats.index', [
            'room_id' => $validated['room_id'],
        ])->with('success', $successMessage);
    }

    /**
     * Đổi loại ghế hàng loạt cho tất cả ghế trong 1 hàng (row_label).
     *
     * Yêu cầu:
     *  - Phòng không được đang trong thời gian chiếu hoặc có booking (trừ CANCELLED/REFUNDED).
     *  - Hàng ghế phải hợp lệ với loại ghế mới (dựa trên computeZones).
     *  - Giá ghế tự động cập nhật theo seat_type mới từ bảng ticket_prices.
     */
    public function bulkUpdateType(Request $request)
    {
        $this->ensureAdminAccess();

        // ── Bước 1: Validate ────────────────────────────────────────────────
        $validated = $request->validate([
            'room_id'       => 'required|exists:rooms,id',
            'row_label'     => ['required', 'string', 'max:1', 'regex:/^[A-Z]$/'],
            'new_seat_type' => 'required|in:STANDARD,VIP,COUPLE',
        ], [
            'row_label.required'       => 'Vui lòng nhập hàng ghế cần đổi loại.',
            'row_label.regex'          => 'Hàng ghế chỉ được là chữ cái A-Z (viết hoa).',
            'row_label.max'            => 'Hàng ghế chỉ 1 ký tự.',
            'new_seat_type.required'   => 'Vui lòng chọn loại ghế mới.',
            'new_seat_type.in'         => 'Loại ghế không hợp lệ. Chỉ chấp nhận: STANDARD, VIP, COUPLE.',
        ]);

        $roomId = (int) $validated['room_id'];
        $rowLabel = strtoupper($validated['row_label']);
        $newSeatType = $validated['new_seat_type'];

        // ── Bước 2: Kiểm tra phòng ──────────────────────────────────────────
        $room = Room::find($roomId);

        if (! $room || $room->status !== 'ACTIVE') {
            return back()
                ->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.'])
                ->withInput();
        }

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatRoomNotLockedForRealtime($room);
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }

        // ── Bước 3: Validate hàng ghế tồn tại trong phòng ──────────────────
        $zones = $this->computeZones($room);

        if (ord($rowLabel) > ord($zones['maxRow'])) {
            return back()
                ->withErrors(['error' => "Phòng {$room->name} chỉ có tối đa đến hàng {$zones['maxRow']}. Hàng {$rowLabel} không tồn tại trong phòng này."])
                ->withInput();
        }

        // ── Bước 4: Lấy tất cả ghế trong hàng ──────────────────────────────
        $seats = Seat::query()
            ->where('room_id', $roomId)
            ->where('row_label', $rowLabel)
            ->get();

        if ($seats->isEmpty()) {
            return back()
                ->withErrors(['error' => "Hàng {$rowLabel} không có ghế nào trong phòng {$room->name}."])
                ->withInput();
        }

        // Chặn nếu bất kỳ ghế nào trong hàng đang được khách đặt/giữ (SOLD/HELD)
        foreach ($seats as $seat) {
            try {
                $this->assertSeatNotUsed($seat);
            } catch (\Exception $e) {
                return back()
                    ->withErrors(['error' => "Hàng {$rowLabel} không thể đổi loại vì: ".$e->getMessage()])
                    ->withInput();
            }
        }

        // ── Bước 5: Tính giá mới ────────────────────────────────────────────
        $newPrice = $this->getSeatPriceFromTicketPrices($newSeatType, $roomId);
        $oldType = $seats->first()->seat_type;

        // ── Bước 6: Cập nhật trong transaction ──────────────────────────────
        $updatedCount = 0;
        $updatedCodes = [];

        try {
            DB::transaction(function () use ($seats, $newSeatType, $newPrice, &$updatedCount, &$updatedCodes) {
                foreach ($seats as $seat) {
                    $oldData = $seat->only(['seat_type', 'price']);

                    $seat->update([
                        'seat_type' => $newSeatType,
                        'price' => $newPrice,
                    ]);

                    $updatedCodes[] = $seat->seat_code;

                    // Ghi audit log cho từng ghế
                    $this->writeAuditLog('seat.bulk_update_type', $seat, $oldData, [
                        'seat_type' => $newSeatType,
                        'price' => $newPrice,
                    ]);

                    $updatedCount++;
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['error' => 'Không thể cập nhật loại ghế. Lỗi: '.$e->getMessage()])
                ->withInput();
        }

        // ── Bước 7: Đồng bộ showtime_seats ──────────────────────────────────
        $this->ensureShowtimeSeatsForRoom($roomId);

        // ── Bước 8: Kết quả ─────────────────────────────────────────────────
        $updatedList = implode(', ', $updatedCodes);

        return redirect()->route('admin.seats.index', ['room_id' => $roomId])
            ->with('success', "✅ Đã đổi loại {$updatedCount} ghế hàng {$rowLabel} từ '{$oldType}' → '{$newSeatType}'. Ghế: {$updatedList}. Giá mới: ".number_format($newPrice)."đ/ghế.");
    }

    public function destroyMany(Request $request)
    {
        $this->ensureAdminAccess();

        // Chặn theo phòng: nếu phòng đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        // thì không được xóa nhiều.

        $validated = $request->validate([
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        $seatIds = $validated['seat_ids'];

        // Check khóa theo phòng cho mọi ghế được chọn
        foreach ($seatIds as $seatId) {
            $seat = Seat::with('room')->findOrFail($seatId);
            try {
                $this->assertSeatNotLockedForRealtime($seat);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        }
        $deleted = [];
        $blocked = [];

        foreach ($seatIds as $seatId) {
            $seat = Seat::withTrashed()->find($seatId);

            if (! $seat) {
                $blocked[] = 'ID '.$seatId;
                continue;
            }

            // Chặn nếu ghế đang được khách đặt/giữ (SOLD/HELD)
            try {
                $this->assertSeatNotUsed($seat);
            } catch (\Exception $e) {
                $blocked[] = $seat->seat_code;
                continue;
            }

            $seat->delete();
            $deleted[] = $seat->seat_code;
            $this->writeAuditLog('seat.delete_soft', $seat, ['status' => $seat->status], ['deleted' => true]);
        }

        if (! empty($blocked)) {
            return back()->withErrors([
                'error' => 'Không thể xóa các ghế: '.implode(', ', $blocked).' vì đang thuộc suất chiếu.',
            ]);
        }

        if (! empty($deleted)) {
            return back()->with('success', 'Đã xóa mềm '.count($deleted).' ghế: '.implode(', ', $deleted).'.');
        }

        return back()->withErrors(['error' => 'Không có ghế nào được chọn để xóa.']);
    }

    public function toggleLock($id)
    {
        $this->ensureAdminAccess();

        $seat = Seat::withTrashed()->find($id);

        if (! $seat) {
            return back()->withErrors(['error' => 'Không tìm thấy ghế này.']);
        }

        if ($seat->trashed()) {
            return back()->withErrors(['error' => 'Ghế này đã bị xóa mềm trước đó.']);
        }

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatNotLockedForRealtime($seat);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        // Chặn khi ghế đang được khách đặt/giữ (SOLD/HELD)
        try {
            $this->assertSeatNotUsed($seat);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        if ($seat->status === 'BROKEN') {
            return back()->withErrors([
                'error' => "Ghế {$seat->seat_code} đang bị hỏng, không thể khóa/mở khóa.",
            ]);
        }

        // 1. Chỉ kiểm tra duy nhất trạng thái 'BLOCKED' để thống nhất
        $isCurrentlyBlocked = ($seat->status === 'BLOCKED');

        // 2. Chuyển đổi trạng thái
        $oldStatus = $seat->status;
        $newStatus = $isCurrentlyBlocked ? 'ACTIVE' : 'BLOCKED';

        // 3. Cập nhật ghế gốc
        $seat->update(['status' => $newStatus]);

        // Tự động cập nhật ghế còn lại nếu là COUPLE
        $siblingSeat = null;
        if ($seat->seat_type === 'COUPLE') {
            $num = $seat->seat_number;
            $siblingNum = $num % 2 === 1 ? $num + 1 : $num - 1;
            $siblingSeat = Seat::where('room_id', $seat->room_id)
                ->where('row_label', $seat->row_label)
                ->where('seat_number', $siblingNum)
                ->first();

            if ($siblingSeat && $siblingSeat->status !== 'BROKEN') {
                $siblingSeat->update(['status' => $newStatus]);
            }
        }

        // 4. Đồng bộ ngay lập tức sang các suất chiếu tương lai
        $this->syncShowtimeSeatState($seat->fresh());
        if (isset($siblingSeat)) {
            $this->syncShowtimeSeatState($siblingSeat->fresh());
        }

        // 5. Ghi log
        $this->writeAuditLog(
            $newStatus === 'BLOCKED' ? 'seat.block' : 'seat.unblock',
            $seat,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );

        $message = $newStatus === 'BLOCKED'
            ? "Đã khóa ghế {$seat->seat_code}. Ghế này sẽ không được khách chọn."
            : "Đã mở khóa ghế {$seat->seat_code}. Ghế có thể được dùng lại.";

        return back()->with('success', $message);
    }

    public function toggleLockMany(Request $request)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'seat_ids' => 'required|array|min:1',
            'seat_ids.*' => 'required|integer|distinct|exists:seats,id',
        ]);

        $seatIds = array_values($validated['seat_ids']);

        // Load seats with room for realtime lock validation
        $seats = Seat::with('room')->whereIn('id', $seatIds)->get();
        if ($seats->count() !== count($seatIds)) {
            return back()->withErrors(['error' => 'Danh sách ghế không hợp lệ.']);
        }

        // Validate per room: room cannot have started showtime/open or non-cancelled booking
        $rooms = $seats->pluck('room')->filter();
        $roomsUnique = $rooms->unique('id')->values();
        foreach ($roomsUnique as $room) {
            try {
                $this->assertSeatRoomNotLockedForRealtime($room);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        $updatedCount = 0;

        // Chặn nếu bất kỳ ghế nào đang được khách đặt/giữ
        foreach ($seats as $seat) {
            try {
                $this->assertSeatNotUsed($seat);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => "Ghế {$seat->seat_code}: ".$e->getMessage()]);
            }
        }

        DB::transaction(function () use ($seats, &$updatedCount) {
            foreach ($seats as $seat) {
                if ($seat->status === 'BROKEN') {
                    // Skip broken seats silently (UI should already avoid, but keep safe)
                    continue;
                }

                $newStatus = $seat->status === 'BLOCKED' ? 'ACTIVE' : 'BLOCKED';
                $oldStatus = $seat->status;

                $seat->update(['status' => $newStatus]);

                // Couple: toggle/update both seats
                if ($seat->seat_type === 'COUPLE') {
                    $siblingNum = ($seat->seat_number % 2 === 1) ? $seat->seat_number + 1 : $seat->seat_number - 1;
                    $siblingSeat = Seat::where('room_id', $seat->room_id)
                        ->where('row_label', $seat->row_label)
                        ->where('seat_number', $siblingNum)
                        ->first();

                    if ($siblingSeat && $siblingSeat->status !== 'BROKEN') {
                        $siblingSeat->update(['status' => $newStatus]);
                    }
                }

                // Sync future showtime seats
                $this->syncShowtimeSeatState($seat->fresh());

                if ($seat->seat_type === 'COUPLE') {
                    $siblingNum = ($seat->seat_number % 2 === 1) ? $seat->seat_number + 1 : $seat->seat_number - 1;
                    $siblingSeat = Seat::where('room_id', $seat->room_id)
                        ->where('row_label', $seat->row_label)
                        ->where('seat_number', $siblingNum)
                        ->first();

                    if ($siblingSeat) {
                        $this->syncShowtimeSeatState($siblingSeat->fresh());
                    }
                }

                $this->writeAuditLog(
                    $newStatus === 'BLOCKED' ? 'seat.bulk_block' : 'seat.bulk_unblock',
                    $seat,
                    ['status' => $oldStatus],
                    ['status' => $newStatus]
                );

                $updatedCount++;
            }
        });

        return back()->with('success', 'Đã cập nhật trạng thái '.$updatedCount.' ghế (toggle).');
    }

    public function destroy($id)
    {
        $this->ensureAdminAccess();

        $seat = Seat::withTrashed()->find($id);

        if (! $seat) {
            return back()->withErrors(['error' => 'Không tìm thấy ghế này.']);
        }

        if ($seat->trashed()) {
            return back()->withErrors(['error' => 'Ghế này đã bị xóa mềm trước đó.']);
        }

        // Chặn khi đã bắt đầu chiếu hoặc có booking (trừ CANCELLED/REFUNDED)
        try {
            $this->assertSeatNotLockedForRealtime($seat);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        // Chặn khi ghế đang được khách đặt/giữ (SOLD/HELD)
        try {
            $this->assertSeatNotUsed($seat);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        // Gợi ý cho phương thức destroy
        if ($seat->showtimeSeats()->whereHas('showtime', function ($q) {
            $q->where('start_time', '>', now()); // Chỉ chặn nếu suất chiếu chưa diễn ra
        })->exists()) {
            return back()->withErrors(['error' => 'Ghế này đang thuộc suất chiếu sắp diễn ra.']);
        }

        $siblingSeat = null;
        if ($seat->seat_type === 'COUPLE') {
            $num = $seat->seat_number;
            $siblingNum = $num % 2 === 1 ? $num + 1 : $num - 1;
            $siblingSeat = Seat::where('room_id', $seat->room_id)
                ->where('row_label', $seat->row_label)
                ->where('seat_number', $siblingNum)
                ->first();

            if ($siblingSeat && $siblingSeat->showtimeSeats()->whereHas('showtime', function ($q) {
                $q->where('start_time', '>', now());
            })->exists()) {
                return back()->withErrors(['error' => 'Ghế cặp này đang thuộc suất chiếu sắp diễn ra.']);
            }
        }

        $seat->delete();
        $this->writeAuditLog('seat.delete_soft', $seat, ['status' => $seat->status], ['deleted' => true]);

        if ($siblingSeat) {
            $siblingSeat->delete();
            $this->writeAuditLog('seat.delete_soft', $siblingSeat, ['status' => $siblingSeat->status], ['deleted' => true]);
        }

        return back()->with('success', "Đã xóa mềm ghế {$seat->seat_code}".($siblingSeat ? " và {$siblingSeat->seat_code}" : '').'.');
    }

    private function writeAuditLog(string $action, Seat $seat, array $oldValue, array $newValue): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_name' => 'seats',
            'entity_id' => (string) $seat->id,
            'old_value' => json_encode($oldValue, JSON_UNESCAPED_UNICODE),
            'new_value' => json_encode($newValue, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }
}
