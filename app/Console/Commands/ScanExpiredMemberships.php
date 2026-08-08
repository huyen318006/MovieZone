<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Command;

class ScanExpiredMemberships extends Command
{
    /**
     * Tên lệnh Artisan để gọi qua terminal hoặc Cron Job hàng ngày.
     *
     * @var string
     */
    protected $signature = 'membership:scan-expired';

    /**
     * Mô tả chi tiết chức năng lệnh.
     *
     * @var string
     */
    protected $description = 'Quét tự động toàn bộ tài khoản Membership để kiểm tra mốc gia hạn 6 tháng (tự động hạ hạng nếu không mua vé).';

    /**
     * Thực thi Artisan Command.
     */
    public function handle(MembershipService $membershipService): int
    {
        $this->info('🚀 Bắt đầu quét kiểm tra hạn duy trì Hạng Thành Viên 6 tháng...');

        $result = $membershipService->processExpiredMemberships();

        $this->table(
            ['Chỉ số', 'Số lượng'],
            [
                ['Tổng số tài khoản đã quét', $result['processed']],
                ['Số tài khoản đủ điều kiện được gia hạn 6 tháng', $result['extended']],
                ['Số tài khoản bị hạ hạng do quá 6 tháng không mua vé', $result['downgraded']],
            ]
        );

        $this->info('✅ Đã hoàn tất quét và hạ/gia hạn hạng thành viên tự động!');

        return Command::SUCCESS;
    }
}
