<?php

namespace App\Http\Controllers;

use App\Models\MembershipLevel;
use App\Models\UserMembership;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    public function index(MembershipService $membershipService)
    {
        $user = Auth::user();
        $userMembership = $membershipService->ensureMembership($user);

        // Nạp thông tin level hiện tại và balance coin
        $userMembership->load('level');
        $coins = $user->coin?->balance ?? 0;

        // Tất cả 5 mốc hạng
        $levels = MembershipLevel::orderBy('min_points', 'asc')->get();

        // Tìm hạng hiện tại dựa theo số dư Coin
        $currentLevel = $levels->where('min_points', '<=', $coins)->last() ?? $levels->first();

        // Cập nhật lại level_id nếu số dư coin phù hợp với mốc hạng lớn hơn
        if ($currentLevel && $currentLevel->id != $userMembership->level_id) {
            $userMembership->update(['level_id' => $currentLevel->id]);
            $userMembership->level = $currentLevel;
        }

        // Tìm hạng tiếp theo
        $nextLevel = $levels->where('min_points', '>', $coins)->first();

        // Tính số coin còn thiếu và phần trăm tiến độ
        if ($nextLevel) {
            $pointsNeeded = $nextLevel->min_points - $coins;
            $prevMinPoints = $currentLevel ? $currentLevel->min_points : 0;
            $range = max(1, $nextLevel->min_points - $prevMinPoints);
            $progress = min(100, max(0, round((($coins - $prevMinPoints) / $range) * 100)));
        } else {
            $pointsNeeded = 0;
            $progress = 100;
        }

        return view('membership.index', compact(
            'user',
            'userMembership',
            'coins',
            'levels',
            'currentLevel',
            'nextLevel',
            'pointsNeeded',
            'progress'
        ));
    }
}