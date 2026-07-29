<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Models\UserMembership;

class MembershipService
{
    /**
     * Tự động khởi tạo ví Coin và Membership mặc định cho Customer nếu chưa có.
     */
    public function ensureMembership(User $user): UserMembership
    {
        // 1. Tạo ví Coin số dư = 0 nếu chưa có
        Coin::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // 2. Tìm hạng mặc định (hạng có min_points thấp nhất, thường là BRONZE)
        $defaultLevel = MembershipLevel::orderBy('min_points', 'asc')->first();
        $defaultLevelId = $defaultLevel ? $defaultLevel->id : 1;

        // 3. Khởi tạo UserMembership nếu chưa có
        return UserMembership::firstOrCreate(
            ['user_id' => $user->id],
            [
                'level_id' => $defaultLevelId,
                'points' => 0,
                'total_spent' => 0,
                'updated_at' => now(),
            ]
        );
    }
}