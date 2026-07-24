<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\StaffDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffDashboardController extends Controller
{
    /**
     * UC-STAFF-05: Staff Dashboard foundation.
     */
    public function index(Request $request, StaffDashboardService $dashboardService)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ]);

        $startDate = !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : today()->startOfDay();
        $endDate = !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : today()->endOfDay();

        try {
            $dashboard = $dashboardService->getOverview($startDate, $endDate);
            $dashboardError = null;
        } catch (\Throwable $e) {
            Log::warning('Staff dashboard load failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            $dashboard = $dashboardService->emptyOverview($startDate, $endDate);
            $dashboardError = 'Không thể tải đầy đủ dữ liệu Dashboard. Vui lòng tải lại trang hoặc thử lại sau.';
        }

        return view('staff.dashboard', compact('dashboard', 'dashboardError'));
    }
}
