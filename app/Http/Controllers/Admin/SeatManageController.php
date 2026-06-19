<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeatManageController extends Controller
{
    private array $allowedStatuses = ['ACTIVE', 'BLOCKED', 'BROKEN'];

    /**
     * Lấy giá ghế theo seat_type từ bảng ticket_prices.
     * Do hiện hệ thống chưa truyền day_type/time_type nên lấy bản ghi ACTIVE đầu tiên matching cinema + seat_type.
     */
    private function getSeatPriceFromTicketPrices(string $seatType, int $cinemaId): float
    {
        // Ưu tiên match theo cinema_id của rạp/phòng
        $ticketPrice = \App\Models\TicketPrice::query()
            ->where('seat_type', $seatType)
            ->where('cinema_id', $cinemaId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('id')
            ->first();

        if ($ticketPrice) {
            return (float) $ticketPrice->price;
        }

        // Fallback: nếu hệ thống seed/record chưa khớp cinema_id, lấy record ACTIVE bất kỳ theo seat_type
        $ticketPriceAnyCinema = \App\Models\TicketPrice::query()
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

        if (!$hasPermission) {
            abort(403, 'Bạn không có quyền quản lý ghế.');
        }
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
        ->whereHas('showtime', function($query) {
            $query->where('start_time', '>', now());
        })
        ->get();

    foreach ($showtimeSeats as $showtimeSeat) {
        if (!in_array($showtimeSeat->status, ['HELD', 'SOLD'])) {
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
                    'seat_id'     => $seat->id,
                ],
                [
                    'price'  => $seat->price, // Lấy giá từ bảng Seat gốc
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
        if ($selectedRoom) {
            $seats = Seat::query()
                ->where('room_id', $selectedRoom)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get();
            $seatsGrouped = $seats->groupBy('row_label');
        }

        return view('admin.seats.index', compact(
            'rooms',
            'seatsGrouped',
            'selectedRoom'
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

        return view('admin.seats.edit', compact('seat'));
    }

    public function store(Request $request)
{
    $this->ensureAdminAccess();

    $validated = $request->validate([
        'room_id' => 'required|exists:rooms,id',
        'row_label' => 'required|string|max:10',
        'seat_number' => 'required|integer|min:1',
        'seat_type' => 'required|in:STANDARD,VIP,COUPLE',
        'status' => 'required|in:' . implode(',', $this->allowedStatuses),
    ]);


    // ... (Giữ nguyên các đoạn validate row_label, VIP, BLOCKED, exists ở trên) ...
    $rowLabel = strtoupper($validated['row_label']);
//     |---------------------------------------------
// | VALIDATE RULE THEO HÀNG GHẾ (SYNC BATCH)
// |---------------------------------------------
// */
$vipRows = ['E','F','G','H'];
$coupleRows = ['J','K'];

if ($validated['seat_type'] === 'VIP' && !in_array($rowLabel, $vipRows)) {
    return back()
        ->withErrors(['error' => 'VIP chỉ được đặt ở hàng E-F-G-H'])
        ->withInput();
}

if ($validated['seat_type'] === 'COUPLE' && !in_array($rowLabel, $coupleRows)) {
    return back()
        ->withErrors(['error' => 'COUPLE chỉ được đặt ở hàng J-K'])
        ->withInput();
}

if (
    $validated['seat_type'] === 'STANDARD' &&
    (in_array($rowLabel, $vipRows) || in_array($rowLabel, $coupleRows))
) {
    return back()
        ->withErrors(['error' => "Hàng {$rowLabel} không hợp lệ cho STANDARD"])
        ->withInput();
}
    $seatCode = $rowLabel . $validated['seat_number'];

    // ... (Giữ nguyên đoạn check tồn tại) ...

    try {
        DB::transaction(function () use ($validated, $rowLabel, $seatCode) {
            // 1. Lưu ghế vào bảng seats
            $newSeat = Seat::create(array_merge($validated, [
                'row_label' => $rowLabel,
                'seat_code' => $seatCode,
                'price' => $this->getSeatPriceFromTicketPrices($validated['seat_type'], $validated['room_id']),
            ]));


            // 2. ĐƯA HÀM ĐỒNG BỘ VÀO ĐÂY (Trong transaction)
            // Truyền trực tiếp $newSeat->room_id vào
            $this->ensureShowtimeSeatsForRoom($newSeat->room_id);
        });
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['error' => 'Không thể lưu, có thể ghế đã tồn tại . Vui lòng kiểm tra lại.'])
            ->withInput();
    }

    $room = Room::findOrFail($validated['room_id']);

    return redirect()->route('admin.seats.index', [
        'room_id' => $validated['room_id']
    ])->with('success', "Thêm ghế {$seatCode} thành công.");
}
    public function update(Request $request, $id)
    {
        $this->ensureAdminAccess();

        $seat = Seat::findOrFail($id);
        $validated = $request->validate([
            'row_label' => 'required|string|max:10',
            'seat_number' => 'required|integer|min:1',
            'seat_type' => 'required|in:STANDARD,VIP,COUPLE',
            // Allow LOCKED on form input; controller sẽ normalize về BLOCKED
            'status' => 'required|in:ACTIVE,LOCKED,BLOCKED,BROKEN',
        ]);



        if ($validated['seat_type'] === 'VIP' && strtoupper($validated['row_label']) !== 'F') {
            return back()
                ->withErrors(['error' => 'Ghế VIP chỉ được phép ở hàng F theo cấu hình hệ thống.'])
                ->withInput();
        }

        // Chuẩn hóa: database dùng BLOCKED, form có thể gửi LOCKED
        if ($validated['status'] === 'LOCKED') {
            $validated['status'] = 'BLOCKED';
        }


        $room = $seat->room;
        if (!$room || $room->status !== 'ACTIVE') {
            return back()
                ->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.'])
                ->withInput();
        }

        $rowLabel = strtoupper($validated['row_label']);

        $seatCode = $rowLabel . $validated['seat_number'];


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

        $validated = $request->validate(
            [
                'room_id' => 'required|exists:rooms,id',
                'row_label' => [
                    'required',
                    'string',
                    'max:1',
                    'regex:/^[A-Z]$/'
                ],
                'start' => 'required|integer|between:1,10',
                'end' => 'required|integer|between:1,10|gte:start',
                'seat_type' => 'required|in:STANDARD,VIP,COUPLE',

            ],
            [
                'row_label.required' => 'Vui lòng nhập hàng ghế.',
                'row_label.string' => 'Hàng ghế phải là chữ cái A-Z.',
                'row_label.max' => 'Hàng ghế chỉ 1 ký tự.',
                'row_label.regex' => 'Hàng ghế chỉ được là chữ cái A-Z (không số, không ký tự đặc biệt).',
            ]
        );

        $room = Room::find($validated['room_id']);

        if (!$room || $room->status !== 'ACTIVE') {
            return back()
                ->withErrors(['error' => 'Phòng này hiện không cho phép cấu hình ghế.'])
                ->withInput();
        }

        $rowLabel = strtoupper($validated['row_label']);
        /**
         * CHỈ ĐỊNH KHU VIP / COUPLE
         * còn lại mặc định STANDARD
         */
        $vipRows = ['E', 'F', 'G', 'H'];
        $coupleRows = ['J', 'K'];

        if (in_array($rowLabel, $vipRows)) {
            $expectedType = 'VIP';
        } elseif (in_array($rowLabel, $coupleRows)) {
            $expectedType = 'COUPLE';
        } else {
            $expectedType = 'STANDARD';
        }

        /**
         * validate chéo giữa row và seat_type
         */
        if ($validated['seat_type'] !== $expectedType) {
            return back()->withErrors([
                'error' => "Hàng {$rowLabel} chỉ được phép tạo ghế {$expectedType}."
            ])->withInput();
        }
        // Giới hạn số hàng theo tổng số ghế của phòng
        $maxRow = chr(64 + ceil($room->total_seats / 10));

        if (ord($rowLabel) > ord($maxRow)) {
            return back()
                ->withErrors([
                'error' => "Để đảm bảo chất lượng trải nghiệm xem phim, phòng {$room->name} ({$room->room_type}) chỉ được thiết kế tối đa {$room->total_seats} ghế ngồi. Phòng này chỉ cho phép cấu hình từ hàng A đến {$maxRow}. Quy tắc áp dụng: E-H ghế VIP, J-K ghế Couple.Còn lại ghế thường "
                ])
                ->withInput();
        }

        /*
|--------------------------------------------------------------------------
| QUY TẮC CẤU HÌNH GHẾ TOÀN HỆ THỐNG
|--------------------------------------------------------------------------
|
| A-D  : STANDARD (4 hàng đầu)
| E-H  : VIP
| I    : STANDARD
| J-K  : COUPLE
| L-Z  : STANDARD
|
|--------------------------------------------------------------------------
*/



        /*
|--------------------------------------------------------------------------
| Giới hạn số ghế mỗi lần tạo
|--------------------------------------------------------------------------
*/

        if (($validated['end'] - $validated['start'] + 1) > 20) {
            return back()
                ->withErrors([
                    'error' => 'Chỉ tạo tối đa 20 ghế/lần để tránh lỗi cấu hình.'
                ])
                ->withInput();
        }
        $created = [];
        $skipped = [];

        try {
            DB::transaction(function () use ($validated, $rowLabel, &$created, &$skipped) {
                for ($i = $validated['start']; $i <= $validated['end']; $i++) {
                    $seatCode = $rowLabel . $i;

                    // Nếu đã tồn tại (dù soft-deleted hay không) thì coi như đã tạo/đã tồn tại.
                    $seat = Seat::withTrashed()
                        ->where('room_id', $validated['room_id'])
                        ->where('row_label', $rowLabel)
                        ->where('seat_number', $i)
                        ->first();


                    if ($seat) {
                        $skipped[] = $seatCode;

                        // Nếu ghế đang bị soft delete thì phục hồi luôn.
                        if ($seat->trashed()) {
                            $seat->restore();
                            $seat->update([
                                'seat_type' => $validated['seat_type'],
                                'price' => $this->getSeatPriceFromTicketPrices($validated['seat_type'], $validated['room_id']),
                                'status' => 'ACTIVE',
                            ]);

                            $created[] = $seat->seat_code;
                        }

                        continue;
                    }

                    $seat = Seat::create([
                        'room_id' => $validated['room_id'],
                        'row_label' => $rowLabel,
                        'seat_number' => $i,
                        'seat_code' => $seatCode,
                        'seat_type' => $validated['seat_type'],
                        'price' => $this->getSeatPriceFromTicketPrices($validated['seat_type'], $validated['room_id']),
                        'status' => 'ACTIVE',
                    ]);

                    $created[] = $seat->seat_code;
                }
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['error' => 'Không thể tạo nhiều ghế. Lỗi: ' . $e->getMessage()])
                ->withInput();
        }


        $this->ensureShowtimeSeatsForRoom($validated['room_id']);

        $room = Room::findOrFail($validated['room_id']);

        if (!empty($created)) {
            $successMessage = 'Đã tạo ' . count($created) . ' ghế cho hàng ' . $rowLabel . ': ' . implode(', ', $created) . '.';
        } else {
            $successMessage = 'Không tạo thêm ghế nào vì các ghế đã tồn tại.';
        }

        if (!empty($skipped)) {
            $successMessage .= ' Các ghế đã tồn tại: ' . implode(', ', $skipped) . '.';
        }

        return redirect()->route('admin.seats.index', [
            'room_id' => $validated['room_id'],
        ])->with('success', $successMessage);
    }

    public function destroyMany(Request $request)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'seat_ids' => 'required|array',
            'seat_ids.*' => 'exists:seats,id',
        ]);

        $seatIds = $validated['seat_ids'];
        $deleted = [];
        $blocked = [];

        foreach ($seatIds as $seatId) {
            $seat = Seat::withTrashed()->findOrFail($seatId);

            // Chỉ chặn nếu ghế thực sự đã được khách/suất chiếu sử dụng.
            // Fix: trước đây chặn dựa trên quan hệ showtime (có thể tồn tại do sync), khiến admin tưởng bị 'thuộc suất'
            // dù thực tế chưa có HELD/SOLD.
            // Ta chỉ chặn khi seat có showtime-seat thuộc suất tương lai và state là SOLD/HELD (đã được dùng).
            $hasActiveUsage = $seat->showtimeSeats()
                ->whereHas('showtime', function ($q) {
                    $q->where('start_time', '>', now());
                })
                ->whereIn('status', ['SOLD', 'HELD'])
                ->exists();

            if ($hasActiveUsage) {
                $blocked[] = $seat->seat_code;
                continue;
            }

            // Fix thêm: khách đang "hold" theo realtime đang nằm trong Cache,
            // trong khi showtime_seats.status có thể chưa kịp sync thành HELD.
            // Nếu có cache seat_held_{showtime_id}_{showtime_seat_id} của user khác => chặn xóa.
            $heldBySomeone = $seat->showtimeSeats()
                ->whereHas('showtime', function ($q) {
                    $q->where('start_time', '>', now());
                })
                ->get()
                ->contains(function ($showtimeSeat) {
                    $heldBy = \Illuminate\Support\Facades\Cache::get(
                        'seat_held_' . $showtimeSeat->showtime_id . '_' . $showtimeSeat->id
                    );
                    return $heldBy && $heldBy != Auth::id();
                });

            if ($heldBySomeone) {
                $blocked[] = $seat->seat_code;
                continue;
            }



            $seat->delete();
            $deleted[] = $seat->seat_code;
            $this->writeAuditLog('seat.delete_soft', $seat, ['status' => $seat->status], ['deleted' => true]);
        }


        if (!empty($blocked)) {
            return back()->withErrors([
                'error' => 'Không thể xóa các ghế: ' . implode(', ', $blocked) . ' vì đang thuộc suất chiếu.'
            ]);
        }

        if (!empty($deleted)) {
            return back()->with('success', 'Đã xóa mềm ' . count($deleted) . ' ghế: ' . implode(', ', $deleted) . '.');
        }

        return back()->withErrors(['error' => 'Không có ghế nào được chọn để xóa.']);
    }

   public function toggleLock($id)
{
    $this->ensureAdminAccess();

    $seat = Seat::findOrFail($id);

    // Fix: Nếu ghế đang bị khách khác HOLD (lưu trong cache seat_held_*), không cho admin khóa/mở.
    $heldBySomeone = $seat->showtimeSeats()
        ->whereHas('showtime', function ($q) {
            $q->where('start_time', '>', now());
        })
        ->get()
        ->contains(function ($showtimeSeat) {
            $heldBy = \Illuminate\Support\Facades\Cache::get(
                'seat_held_' . $showtimeSeat->showtime_id . '_' . $showtimeSeat->id
            );
            return $heldBy && $heldBy != Auth::id();
        });

    if ($heldBySomeone) {
        return back()->withErrors(['error' => "Ghế {$seat->seat_code} đang được khách giữ (hold), không thể khóa/mở khóa lúc này."]);
    }


    if ($seat->status === 'BROKEN') {
        return back()->withErrors([
            'error' => "Ghế {$seat->seat_code} đang bị hỏng, không thể khóa/mở khóa."
        ]);
    }

    // 1. Chỉ kiểm tra duy nhất trạng thái 'BLOCKED' để thống nhất
    $isCurrentlyBlocked = ($seat->status === 'BLOCKED');

    // 2. Chuyển đổi trạng thái
    $oldStatus = $seat->status;
    $newStatus = $isCurrentlyBlocked ? 'ACTIVE' : 'BLOCKED';

    // 3. Cập nhật ghế gốc
    $seat->update(['status' => $newStatus]);

    // 4. Đồng bộ ngay lập tức sang các suất chiếu tương lai
    $this->syncShowtimeSeatState($seat->fresh());

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

    public function destroy($id)
    {
        $this->ensureAdminAccess();

        $seat = Seat::findOrFail($id);

        // Gợi ý cho phương thức destroy
        if ($seat->showtimeSeats()->whereHas('showtime', function($q) {
            $q->where('start_time', '>', now()); // Chỉ chặn nếu suất chiếu chưa diễn ra
        })->exists()) {
            return back()->withErrors(['error' => 'Ghế này đang thuộc suất chiếu sắp diễn ra.']);
        }

        $seat->delete();
        $this->writeAuditLog('seat.delete_soft', $seat, ['status' => $seat->status], ['deleted' => true]);

        return back()->with('success', "Đã xóa mềm ghế {$seat->seat_code}.");
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
