<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Models\ShowtimeSeat;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\CheckInLog;
use App\Services\AuditLogService;
use App\Services\CheckInService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingManageController extends Controller
{
    protected $checkInService;

    public function __construct(CheckInService $checkInService)
    {
        $this->checkInService = $checkInService;
    }

    /**
     * Kiểm tra quyền hạn của Admin/Staff (Luồng ngoại lệ E4)
     */
    private function checkPermission(string $permission, bool $isJson = false)
    {
        $user = Auth::user();
        if (!$user) {
            if ($isJson) {
                return response()->json(['message' => 'Vui lòng đăng nhập để tiếp tục.'], 401);
            }
            abort(401, 'Vui lòng đăng nhập để tiếp tục.');
        }

        $hasPermission = DB::table('user_roles')
            ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('user_roles.user_id', $user->id)
            ->where('permissions.name', $permission)
            ->exists();

        if (!$hasPermission) {
            if ($isJson) {
                return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
            }
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return null;
    }

    /**
     * Hiển thị trang quản lý booking (HTML view)
     */
    public function index()
    {
        $permCheck = $this->checkPermission('booking.manage');
        if ($permCheck) return $permCheck;

        return view('admin.bookings.index');
    }

    /**
     * API: Lấy danh sách booking kèm lọc và tìm kiếm (JSON)
     */
    public function list(Request $request)
    {
        $permCheck = $this->checkPermission('booking.manage', true);
        if ($permCheck) return $permCheck;

        $query = Booking::with([
            'user:id,name,email,phone',
            'showtime:id,movie_id,cinema_id,start_time',
            'showtime.movie:id,title',
            'showtime.cinema:id,name',
            'latePaymentAlert',
        ])->orderBy('created_at', 'desc');

        // Tìm kiếm theo Mã booking
        if ($request->filled('booking_code')) {
            $query->where('booking_code', 'like', '%' . trim($request->booking_code) . '%');
        }

        // Tìm kiếm theo thông tin khách hàng (tên, email, số điện thoại)
        if ($request->filled('customer_name') || $request->filled('email') || $request->filled('phone')) {
            $query->whereHas('user', function ($q) use ($request) {
                if ($request->filled('customer_name')) {
                    $q->where('name', 'like', '%' . trim($request->customer_name) . '%');
                }
                if ($request->filled('email')) {
                    $q->where('email', 'like', '%' . trim($request->email) . '%');
                }
                if ($request->filled('phone')) {
                    $q->where('phone', 'like', '%' . trim($request->phone) . '%');
                }
            });
        }

        // Lọc theo trạng thái booking
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo ngày đặt
        if ($request->filled('booking_date')) {
            $query->whereDate('created_at', $request->booking_date);
        }

        $bookings = $query->paginate($request->input('per_page', 10));

        // Tính toán thống kê cho các thẻ metric
        $stats = [
            'total' => Booking::count(),
            'paid' => Booking::where('status', 'PAID')->count(),
            'pending' => Booking::where('status', 'PENDING')->count(),
            'cancelled' => Booking::where('status', 'CANCELLED')->count(),
            'expired' => Booking::where('status', 'EXPIRED')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'stats' => $stats,
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
                'last_page'    => $bookings->lastPage(),
            ]
        ]);
    }

    /**
     * Chi tiết 1 Booking đầy đủ các thông tin
     */
    public function show($id)
    {
        $permCheck = $this->checkPermission('booking.manage', true);
        if ($permCheck) return $permCheck;

        $booking = Booking::with([
            'user:id,name,phone,email',
            'showtime:id,cinema_id,movie_id,room_id,start_time,end_time',
            'showtime.movie:id,title,poster_url',
            'showtime.cinema:id,name,address',
            'showtime.room:id,name,room_type',
            'bookingSeats:id,booking_id,showtime_seat_id,seat_code,seat_type,price',
            'tickets:id,booking_id,booking_seat_id,ticket_code,qr_code,status,checked_in_at,checked_in_by',
            'tickets.checkedInByUser:id,name',
            'tickets.bookingSeat:id,seat_code',
            'bookingCombos:id,booking_id,combo_id,quantity,unit_price,total_price',
            'bookingCombos.combo:id,name,description',
            'payment:id,booking_id,payment_method,amount,transaction_code,status,paid_at',
            'cancellation.admin',
            'latePaymentAlert',
        ])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy booking'], 404);
        }

        // Lấy voucher usages
        $voucherUsages = DB::table('voucher_usages')
            ->join('vouchers', 'voucher_usages.voucher_id', '=', 'vouchers.id')
            ->where('voucher_usages.booking_id', $booking->id)
            ->select('vouchers.code', 'vouchers.discount_type', 'vouchers.discount_value', 'voucher_usages.used_at')
            ->get();

        // Lấy lịch sử thay đổi (Audit Logs)
        $auditLogs = AuditLog::with('user:id,name')
            ->where('entity_name', 'Booking')
            ->where('entity_id', (string) $booking->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id'           => $log->id,
                    'action'       => $log->action,
                    'performed_by' => $log->user?->name ?? 'Hệ thống',
                    'created_at'   => $log->created_at,
                    'old_value'    => $log->old_value ? json_decode($log->old_value, true) : null,
                    'new_value'    => $log->new_value ? json_decode($log->new_value, true) : null,
                ];
            });

        // Lấy thông tin Hạng thẻ & Bóc tách chi tiết giảm giá
        $tierName = 'BRONZE';
        $tierPercent = 0;
        $tierDiscountAmount = 0;

        if ($booking->user_id) {
            $userMem = \App\Models\UserMembership::with('level')
                ->where('user_id', $booking->user_id)
                ->first();
            if ($userMem && $userMem->level) {
                $tierName = strtoupper($userMem->level->name);
                $tierPercent = (float) ($userMem->level->discount_percent ?? 0);
                $tierDiscountAmount = (float) round(($booking->total_ticket_amount * $tierPercent) / 100);
            }
        }

        // Lấy số Xu khách đã sử dụng cho đơn hàng
        $redeemTx = DB::table('point_transactions')
            ->where('booking_id', $booking->id)
            ->where('type', 'REDEEM')
            ->first();
        $coinDiscountAmount = $redeemTx ? abs((int) $redeemTx->points) : 0;
        $voucherDiscountAmount = (float) max(0, $booking->discount_amount - $tierDiscountAmount - $coinDiscountAmount);

        return response()->json([
            'success'           => true,
            'booking'           => $booking,
            'vouchers'          => $voucherUsages,
            'audit_logs'        => $auditLogs,
            'late_payment'      => $booking->latePaymentAlert,
            'pricing_breakdown' => [
                'tier_name'               => $tierName,
                'tier_percent'            => $tierPercent,
                'tier_discount_amount'    => $tierDiscountAmount,
                'voucher_discount_amount' => $voucherDiscountAmount,
                'coin_discount_amount'    => $coinDiscountAmount,
            ],
        ], 200);
    }

    /**
     * Admin thao tác hủy đơn và giải phóng ghế
     */
    public function cancel(Request $request, $id) 
    {
        $permCheck = $this->checkPermission('booking.manage', true);
        if ($permCheck) return $permCheck;

        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $booking = Booking::with('tickets')->find($id);
        if (!$booking) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng cần hủy'], 404);
        }

        // E3: Nếu đơn này đã ở trạng thái CANCELLED từ trước
        if ($booking->status === 'CANCELLED') {
            return response()->json(['message' => 'Booking này đã được hủy trước đó!'], 400);
        }

        // E2: Nếu booking đã CHECKED_IN hoặc có bất kỳ vé nào đã sử dụng
        $hasUsedTickets = $booking->tickets->where('status', 'USED')->isNotEmpty();
        if ($booking->status === 'CHECKED_IN' || $hasUsedTickets) {
            return response()->json(['message' => 'Vé đã được check-in tại rạp, không thể hủy!'], 400);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $booking->status;
            $oldPaymentStatus = $booking->payment_status;

            // 1. Cập nhật trạng thái booking sang CANCELLED và cập nhật trạng thái thanh toán tương ứng
            $booking->status = 'CANCELLED';
            if ($booking->payment_status === 'UNPAID') {
                $booking->payment_status = 'FAILED';
            } elseif ($booking->payment_status === 'PAID') {
                $booking->payment_status = 'REFUNDED';
            }
            $booking->save();

            // 2. Hủy các vé liên quan (Cập nhật trạng thái vé sang CANCELLED)
            Ticket::where('booking_id', $booking->id)->update(['status' => 'CANCELLED']);

            // 2.5a. Hoàn xu cho số tiền VND thực tế đã thanh toán (final_amount)
            if ($oldPaymentStatus === 'PAID') {
                $refundAmount = (int) round((float) ($booking->final_amount ?? 0));
                if ($refundAmount > 0 && $booking->user_id) {
                    $coin = \App\Models\Coin::firstOrCreate(
                        ['user_id' => $booking->user_id],
                        ['balance' => 0]
                    );
                    $coin->balance = (int) $coin->balance + $refundAmount;
                    $coin->save();

                    \App\Models\PointTransaction::create([
                        'user_id'    => $booking->user_id,
                        'booking_id' => $booking->id,
                        'points'     => $refundAmount,
                        'type'       => 'ADJUST',
                        'created_at' => now(),
                    ]);
                }
            }

            // 2.5b. Hoàn lại số xu gốc đã sử dụng để giảm giá đơn hàng (nếu có)
            $redeemTx = \App\Models\PointTransaction::where('booking_id', $booking->id)
                ->where('type', 'REDEEM')
                ->first();
            if ($redeemTx && $booking->user_id) {
                app(\App\Services\CoinRedemptionService::class)->refundCoins(
                    $booking->user_id,
                    abs($redeemTx->points),
                    $booking->id
                );
            }

            // 2.5c. Mở khóa / Hoàn lại Voucher đã sử dụng cho đơn hàng (nếu có)
            \App\Models\VoucherUsage::where('booking_id', $booking->id)->delete();

            // 2.5d. Thu hồi số xu thưởng (EARN) đã cộng cho đơn hàng này (nếu có)
            $earnTx = \App\Models\PointTransaction::where('booking_id', $booking->id)
                ->where('type', 'EARN')
                ->first();
            if ($earnTx && $earnTx->points > 0 && $booking->user_id) {
                $earnedCoins = abs((int) $earnTx->points);
                $coin = \App\Models\Coin::firstOrCreate(
                    ['user_id' => $booking->user_id],
                    ['balance' => 0]
                );
                $coin->balance = max(0, (int) $coin->balance - $earnedCoins);
                $coin->save();

                \App\Models\PointTransaction::create([
                    'user_id'    => $booking->user_id,
                    'booking_id' => $booking->id,
                    'points'     => -$earnedCoins,
                    'type'       => 'ADJUST',
                    'created_at' => now(),
                ]);
            }

            // 3. Giải phóng ghế trên sơ đồ phòng chiếu
            $showtimeSeatIds = DB::table('booking_seats')
                ->where('booking_id', $booking->id)
                ->pluck('showtime_seat_id');

            if ($showtimeSeatIds->isNotEmpty()) {
                ShowtimeSeat::whereIn('id', $showtimeSeatIds)->update(['status' => 'AVAILABLE']);
            }

            // 4. Lưu lý do hủy vào bảng phụ
            BookingCancellation::create([
                'booking_id'  => $booking->id,
                'canceled_by' => Auth::id(),
                'reason'      => $request->reason
            ]);

            // 5. Ghi nhận lịch sử thay đổi (Audit Log)
            AuditLogService::log(
                'CANCEL_BOOKING',
                'Booking',
                $booking->id,
                ['status' => $oldStatus],
                [
                    'status' => 'CANCELLED',
                    'reason' => $request->reason,
                    'canceled_by' => Auth::user()?->name,
                ]
            );

            DB::commit();
            return response()->json(['message' => 'Hủy booking và giải phóng ghế thành công!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống, không thể cập nhật trạng thái booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hỗ trợ check-in vé của booking
     */
    public function checkInTicket(Request $request, $ticketId)
    {
        $permCheck = $this->checkPermission('booking.manage', true);
        if ($permCheck) return $permCheck;

        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        $adminId = Auth::id();
        
        // Gọi dịch vụ check-in để xác nhận
        $result = $this->checkInService->confirmCheckIn($ticketId, $adminId, 'MANUAL');

        if ($result['success']) {
            // Ghi nhận lý do hỗ trợ check-in của Admin vào Audit Log
            AuditLogService::log(
                'ADMIN_MANUAL_CHECK_IN',
                'Ticket',
                $ticketId,
                ['status' => 'UNUSED'],
                [
                    'status' => 'USED',
                    'reason' => $request->reason,
                    'action_by' => Auth::user()?->name
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in thành công!',
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']['message'] ?? 'Check-in thất bại.'
        ], 400);
    }
}
