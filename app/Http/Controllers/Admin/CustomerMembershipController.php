<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MembershipLevel;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\MembershipService;
use Illuminate\Http\Request;

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
     * Xem chi tiết Membership & Lịch sử mua vé / tích Coin của 1 Khách hàng
     */
    public function show($id, MembershipService $membershipService)
    {
        $customer = User::with(['coin', 'membership.level'])->findOrFail($id);
        $membershipService->ensureMembership($customer);

        // Danh sách đơn vé đã thanh toán thành công (Lịch sử chi tiêu)
        $bookings = Booking::where('user_id', $customer->id)
            ->where('status', 'PAID')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Lịch sử biến động Coin (Lịch sử tích/trừ điểm)
        $pointTransactions = PointTransaction::where('user_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.memberships.show', compact('customer', 'bookings', 'pointTransactions'));
    }
}