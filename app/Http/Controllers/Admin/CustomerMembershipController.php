<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MembershipLevel;
use App\Models\MembershipLevelHistory;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMembershipController extends Controller
{
    /**
     * Danh sách Membership Khách Hàng phía Admin
     */
    public function index(Request $request, MembershipService $membershipService)
    {
        $search = $request->input('search');
        $levelId = $request->input('level_id');

        // Chỉ lấy các tài khoản có Role Customer (role_id = 3)
        $query = User::whereHas('roles', function ($q) {
            $q->where('roles.id', 3);
        })->with(['coin', 'membership.level']);

        // Tìm kiếm theo tên, email, số điện thoại
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo mốc Hạng
        if (!empty($levelId)) {
            $query->whereHas('membership', function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Đảm bảo các customer đều có ví Coin & Membership
        foreach ($customers as $cust) {
            if (!$cust->membership) {
                $membershipService->ensureMembership($cust);
                $cust->load(['coin', 'membership.level']);
            }
        }

        $levels = MembershipLevel::orderBy('min_points', 'asc')->get();

        return view('admin.memberships.index', compact('customers', 'levels', 'search', 'levelId'));
    }

    /**
     * Xem chi tiết Membership & Lịch sử mua vé / tích Coin / Lịch sử thăng hạng của 1 Khách hàng
     */
    public function show($id, MembershipService $membershipService)
    {
        $customer = User::with(['coin', 'membership.level'])->findOrFail($id);
        $membershipService->ensureMembership($customer);

        // Danh sách đơn vé đã thanh toán thành công (Lịch sử chi tiêu)
        $bookings = Booking::where('user_id', $customer->id)
            ->where('status', 'PAID')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Lịch sử biến động Coin (Lịch sử tích/trừ điểm)
        $pointTransactions = PointTransaction::where('user_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Lịch sử biến động Hạng (Thăng / Hạ / Gia hạn hạng)
        $levelHistories = MembershipLevelHistory::with(['oldLevel', 'newLevel'])
            ->where('user_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.memberships.show', compact('customer', 'bookings', 'pointTransactions', 'levelHistories'));
    }

    /**
     * Admin điều chỉnh Coin thủ công cho khách hàng
     */
    public function adjustCoin(Request $request, $id, MembershipService $membershipService)
    {
        $request->validate([
            'action_type' => 'required|in:ADD,DEDUCT',
            'amount'      => 'required|integer|min:1',
            'reason'      => 'required|string|max:255',
        ], [
            'action_type.required' => 'Vui lòng chọn loại điều chỉnh (Cộng hoặc Trừ).',
            'action_type.in'       => 'Loại điều chỉnh không hợp lệ.',
            'amount.required'      => 'Vui lòng nhập số Coin.',
            'amount.integer'       => 'Số Coin phải là số nguyên.',
            'amount.min'           => 'Số Coin phải lớn hơn 0.',
            'reason.required'      => 'Vui lòng nhập lý do điều chỉnh bắt buộc.',
            'reason.max'           => 'Lý do tối đa 255 ký tự.',
        ]);

        try {
            $customer = User::findOrFail($id);
            $adminUser = \App\Helpers\TabAuthHelper::currentUser() ?? Auth::user();
            $adminUserId = $adminUser ? $adminUser->id : Auth::id();

            $membershipService->adjustCoinManually(
                $customer,
                (int) $request->amount,
                $request->action_type,
                $request->reason,
                $adminUserId
            );

            $actionText = ($request->action_type === 'ADD') ? 'Cộng' : 'Trừ';
            return redirect()->back()->with('success', "Đã {$actionText} " . number_format($request->amount) . " Coin cho khách hàng thành công!");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Quét tự động kiểm tra hết hạn duy trì hạng 6 tháng của tất cả khách hàng
     */
    public function scanExpired(MembershipService $membershipService)
    {
        $result = $membershipService->processExpiredMemberships();

        return redirect()->back()->with(
            'success',
            "Quét kiểm tra thành công! Đã xử lý {$result['processed']} tài khoản quá hạn duy trì ({$result['extended']} tài khoản được gia hạn, {$result['downgraded']} tài khoản tự động hạ hạng)."
        );
    }

    /**
     * Admin Reset Hạng về BRONZE nhưng GIỮ NGUYÊN tổng chi tiêu lịch sử mua vé (total_spent)
     */
    public function resetLevel(Request $request, $id, MembershipService $membershipService)
    {
        try {
            $customer = User::with('membership.level')->findOrFail($id);
            $membershipService->ensureMembership($customer);

            $userMembership = $customer->membership;
            $oldLevelId = $userMembership->level_id;
            $oldLevelName = $userMembership->level?->name ?? 'BRONZE';

            $bronzeLevel = MembershipLevel::where('name', 'BRONZE')->first();
            $bronzeLevelId = $bronzeLevel ? $bronzeLevel->id : $oldLevelId;

            $resetSpent = $request->boolean('reset_spent', false);
            $newSpent = $resetSpent ? 0 : $userMembership->total_spent;

            // Reset level_id về BRONZE, giữ nguyên hoặc reset total_spent theo yêu cầu
            $userMembership->update([
                'level_id' => $bronzeLevelId,
                'total_spent' => $newSpent,
                'level_expired_at' => now()->addMonths(6),
                'updated_at' => now(),
            ]);

            $reasonNote = $resetSpent
                ? 'Admin reset hạng về BRONZE và đặt tổng chi tiêu về 0đ thủ công'
                : 'Admin reset hạng về BRONZE (Giữ nguyên lịch sử chi tiêu mua vé thực tế)';

            // Ghi nhận nhật ký biến động Hạng
            MembershipLevelHistory::create([
                'user_id'      => $customer->id,
                'old_level_id' => $oldLevelId,
                'new_level_id' => $bronzeLevelId,
                'reason'       => $reasonNote,
            ]);

            // Ghi nhận Audit Log
            $adminUser = \App\Helpers\TabAuthHelper::currentUser() ?? Auth::user();
            $adminUserId = $adminUser ? $adminUser->id : Auth::id();

            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::create([
                    'user_id'     => $adminUserId,
                    'action'      => 'RESET_MEMBERSHIP_LEVEL',
                    'entity_name' => 'UserMembership',
                    'entity_id'   => $customer->id,
                    'old_value'   => "Hạng: {$oldLevelName}, Chi tiêu: " . number_format($userMembership->total_spent ?? 0) . "đ",
                    'new_value'   => "Reset về Hạng BRONZE (Chi tiêu: " . number_format($newSpent) . "đ)",
                    'created_at'  => now(),
                ]);
            }

            return redirect()->back()->with('success', "Đã reset hạng của khách hàng {$customer->name} về BRONZE thành công (Tổng chi tiêu thực tế: " . number_format($newSpent) . "đ)!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi reset hạng: ' . $e->getMessage());
        }
    }
}