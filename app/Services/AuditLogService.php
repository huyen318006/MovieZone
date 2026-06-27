<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * S1-07: Audit Log Service
 *
 * Ghi lại mọi hành động của Staff khi tra cứu booking.
 * Tuân thủ BR02: Ghi Audit Log cho mọi thao tác.
 */
class AuditLogService
{
    /**
     * Ghi audit log.
     *
     * @param string     $action     Mã hành động (VD: BOOKING_SEARCH, BOOKING_VIEW_DETAIL)
     * @param string     $entityName Tên entity (VD: Booking, Ticket)
     * @param string|int $entityId   ID của entity
     * @param mixed      $oldValue   Giá trị cũ (nullable)
     * @param mixed      $newValue   Giá trị mới / dữ liệu bổ sung (nullable)
     */
    public static function log(
        string $action,
        string $entityName,
        string|int $entityId,
        mixed $oldValue = null,
        mixed $newValue = null
    ): void {
        $enrichedNewValue = null;

        if ($newValue !== null) {
            $data = is_array($newValue) ? $newValue : ['data' => $newValue];
            $data['ip'] = request()->ip();
            $data['user_agent'] = request()->userAgent();
            $enrichedNewValue = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_name' => $entityName,
            'entity_id'   => (string) $entityId,
            'old_value'   => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
            'new_value'   => $enrichedNewValue,
            'created_at'  => now(),
        ]);
    }
}
