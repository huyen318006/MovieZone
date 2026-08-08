<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\DailyCheckin;
use App\Models\MembershipLevel;
use App\Models\PointTransaction;
use App\Models\UserMembership;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    public function index(MembershipService $membershipService)
    {
        $user = \App\Helpers\TabAuthHelper::currentUser() ?? Auth::user();
        $userMembership = $membershipService->ensureMembership($user);

        // Nạp thông tin level hiện tại và balance coin
        $userMembership->load('level');
        $coins = $user->coin?->balance ?? 0;

        // 1. Tính toán trạng thái điểm danh hôm nay & chuỗi streak
        $checkedToday = DailyCheckin::where('user_id', $user->id)
            ->whereDate('checkin_date', Carbon::today())
            ->exists();

        $streak = 0;
        $data = $checkedToday ? Carbon::today() : Carbon::yesterday();

        while (DailyCheckin::where('user_id', $user->id)->whereDate('checkin_date', $data)->exists()) {
            $streak++;
            $data->subDay();
        }

        $rewardTable = [
            1 => 100,
            2 => 150,
            3 => 200,
            4 => 200,
            5 => 200,
            6 => 200,
            7 => 200,
        ];

        $todayStep = $checkedToday ? $streak : ($streak + 1);
        $todayStep = max(1, min(7, (int) $todayStep));
        $todayReward = $rewardTable[$todayStep] ?? $rewardTable[1];

        // 2. Tất cả 5 mốc hạng & Lấy Hạng hiện tại của Khách hàng
        $totalSpent = (float) ($userMembership->total_spent ?? 0);
        $levels = MembershipLevel::orderBy('min_points', 'asc')->get();

        // Hạng thực tế của khách hàng từ DB
        $currentLevel = $userMembership->level ?? $levels->first();

        // Tìm hạng tiếp theo có mốc min_points lớn hơn Hạng hiện tại
        $nextLevel = $levels->where('min_points', '>', $currentLevel->min_points)->first();

        // Tính số tiền cần chi tiêu thêm và % tiến độ dựa trên khoảng chênh lệch giữa Hạng hiện tại và Hạng kế tiếp
        if ($nextLevel && $nextLevel->min_points > $currentLevel->min_points) {
            $stepTotal = $nextLevel->min_points - $currentLevel->min_points;

            if ($totalSpent >= $nextLevel->min_points) {
                // Khách hàng bị hạ hạng quá hạn hoặc reset hạng thủ công:
                // Cần chi tiêu lại đủ nấc chênh lệch giữa Hạng hiện tại và Hạng kế tiếp
                $pointsNeeded = $stepTotal;
                $progress = 0;
            } else {
                // Tiến trình tích lũy bình thường
                $pointsNeeded = max(0, $nextLevel->min_points - $totalSpent);
                $spentInStep = max(0, $totalSpent - $currentLevel->min_points);
                $progress = min(100, max(0, round(($spentInStep / $stepTotal) * 100)));
            }
        } else {
            $pointsNeeded = 0;
            $progress = 100;
        }

        // 3. Truy xuất Kho Voucher của Customer
        $vouchers = $membershipService->getUserVouchers($user);

        return view('membership.index', compact(
            'user',
            'userMembership',
            'coins',
            'levels',
            'currentLevel',
            'nextLevel',
            'pointsNeeded',
            'progress',
            'checkedToday',
            'streak',
            'todayStep',
            'todayReward',
            'rewardTable',
            'vouchers'
        ));
    }

    /**
     * Xem lịch sử tích điểm và biến động Coin của Customer
     */
    public function history()
    {
        $user = \App\Helpers\TabAuthHelper::currentUser() ?? Auth::user();

        $transactions = PointTransaction::with('booking')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('membership.history', compact('user', 'transactions'));
    }
}