<?php

use App\Services\MembershipService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Command quét tự động hạ hạng và gia hạn Membership 6 tháng
 */
Artisan::command('membership:scan-expired', function (MembershipService $membershipService) {
    $this->info('Đang tiến hành quét tự động quá hạn membership...');
    $result = $membershipService->processExpiredMemberships();
    $this->info("Quét hoàn tất! Đã xử lý {$result['processed']} tài khoản ({$result['extended']} gia hạn, {$result['downgraded']} hạ hạng).");
})->purpose('Tự động quét hạ hạng và gia hạn membership 6 tháng');

// Tự động lên lịch chạy ngầm hằng ngày lúc 00:00
Schedule::command('membership:scan-expired')->dailyAt('00:00');
