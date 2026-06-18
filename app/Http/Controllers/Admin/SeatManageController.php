<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cinema;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class SeatManageController extends Controller
{
    // READ: Hiển thị sơ đồ ghế chính
    public function index(Request $request)
    {
        $cinemas = Cinema::all();
        $rooms = [];
        $seatsGrouped = [];

        $selectedCinema = $request->get('cinema_id');
        $selectedRoom = $request->get('room_id');

        if ($selectedCinema) {
            $rooms = Room::where('cinema_id', $selectedCinema)->get();
        }

        if ($selectedRoom) {
            $seats = Seat::where('room_id', $selectedRoom)
                ->orderBy('row_label')
                ->orderBy('seat_number')
                ->get();
            $seatsGrouped = $seats->groupBy('row_label');
        }

        return view('admin.seats.index', compact('cinemas', 'rooms', 'seatsGrouped', 'selectedCinema', 'selectedRoom'));
    }

    // CREATE: Hiển thị trang form thêm mới
    public function create(Request $request)
    {
        $room_id = $request->get('room_id');
        $room = Room::with('cinema')->findOrFail($room_id); // Đảm bảo phòng tồn tại

        return view('admin.seats.create', compact('room'));
    }

    // STORE: Xử lý lưu dữ liệu thêm mới
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'row_label' => 'required|string|max:10',
            'seat_number' => 'required|integer|min:1',
            'seat_type' => 'required|in:STANDARD,VIP,COUPLE',
            'status' => 'required|in:ACTIVE,LOCKED,BROKEN',
            'price' => 'required|numeric|min:0',
        ]);

        $room_id = $request->room_id;
        $row_label = strtoupper($request->row_label);
        $seat_number = $request->seat_number;
        $seat_code = $row_label . $seat_number;

        $isDuplicate = Seat::where('room_id', $room_id)->where('seat_code', $seat_code)->exists();

        if ($isDuplicate) {
            return back()->withErrors(['error' => "Ngoại lệ [E1]: Ghế {$seat_code} đã tồn tại!"])->withInput();
        }

        try {
            Seat::create([
                'room_id' => $room_id,
                'row_label' => $row_label,
                'seat_number' => $seat_number,
                'seat_code' => $seat_code,
                'seat_type' => $request->seat_type,
                'status' => $request->status,
                'price' => $request->price,
            ]);

            // Thêm xong quay lại trang sơ đồ của phòng đó luôn
            $room = Room::find($room_id);
            return redirect()->route('admin.seats.index', ['cinema_id' => $room->cinema_id, 'room_id' => $room_id])
                             ->with('success', "Đã thêm thành công ghế {$seat_code}.");
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Ngoại lệ [E4]: Không thể lưu cấu hình ghế.']);
        }
    }

    // EDIT: Hiển thị trang form sửa đổi thông tin
    public function edit($id)
    {
        $seat = Seat::with('room.cinema')->findOrFail($id);
        return view('admin.seats.edit', compact('seat'));
    }

    // UPDATE: Xử lý cập nhật thay đổi
    public function update(Request $request, $id)
    {
        $request->validate([
            'row_label' => 'required|string|max:10',
            'seat_number' => 'required|integer|min:1',
            'seat_type' => 'required|in:STANDARD,VIP,COUPLE',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:ACTIVE,LOCKED,BROKEN',
        ]);

        $seat = Seat::findOrFail($id);
        $row_label = strtoupper($request->row_label);
        $seat_number = $request->seat_number;
        $seat_code = $row_label . $seat_number;

        $isDuplicate = Seat::where('room_id', $seat->room_id)
            ->where('seat_code', $seat_code)
            ->where('id', '!=', $id)
            ->exists();

        if ($isDuplicate) {
            return back()->withErrors(['error' => "Ngoại lệ [E1]: Mã ghế {$seat_code} đã bị trùng!"]);
        }

        try {
            $seat->update([
                'row_label' => $row_label,
                'seat_number' => $seat_number,
                'seat_code' => $seat_code,
                'seat_type' => $request->seat_type,
                'price' => $request->price,
                'status' => $request->status,
            ]);

            $room = Room::find($seat->room_id);
            return redirect()->route('admin.seats.index', ['cinema_id' => $room->cinema_id, 'room_id' => $seat->room_id])
                             ->with('success', "Cập nhật thành công ghế {$seat_code}.");
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Ngoại lệ [E4]: Cập nhật sơ đồ ghế thất bại.']);
        }
    }

    // TOGGLE LOCK: Khóa nhanh tại chỗ
    public function toggleLock($id)
    {
        $seat = Seat::findOrFail($id);
        $seat->status = ($seat->status === 'LOCKED') ? 'ACTIVE' : 'LOCKED';
        $seat->save();

        Log::info("BR06: Admin thay đổi bảo mật ghế {$seat->seat_code}");
        return back()->with('success', "Thay đổi trạng thái ghế {$seat->seat_code} thành công.");
    }

    // DESTROY: Xóa mềm
    public function destroy($id)
    {
        $seat = Seat::findOrFail($id);
        $seatCode = $seat->seat_code;
        $seat->delete(); 
        return back()->with('success', "Đã xóa mềm ghế {$seatCode} khỏi sơ đồ phòng.");
    }
 public function storeBatch(Request $request)
{
    $count = (int)$request->end - (int)$request->start + 1;
    if ($count > 10) return back()->withErrors(['error' => 'Chỉ tạo tối đa 10 ghế!']);

    for ($i = (int)$request->start; $i <= (int)$request->end; $i++) {
        Seat::updateOrCreate(
            ['room_id' => $request->room_id, 'seat_code' => strtoupper($request->row_label) . $i],
            [
                'row_label' => strtoupper($request->row_label),
                'seat_number' => $i,
                'seat_type' => $request->seat_type,
                'price' => $request->price,
                'status' => 'ACTIVE'
            ]
        );
    }
    $room = Room::find($request->room_id);
    return redirect()->route('admin.seats.index', ['cinema_id' => $room->cinema_id, 'room_id' => $request->room_id])
                     ->with('success', 'Đã tạo xong!');
}

public function destroyMultiple(Request $request)
{
    Seat::whereIn('id', $request->seat_ids ?? [])->delete();
    return back()->with('success', 'Đã xóa các ghế chọn!');
}
}