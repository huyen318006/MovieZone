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
        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : today();

        try {
            $dashboard = $dashboardService->getDailyOverview($date);
            $dashboardError = null;
        } catch (\Throwable $e) {
            Log::warning('Staff dashboard load failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            $dashboard = $dashboardService->emptyOverview($date);
            $dashboardError = 'Không thể tải đầy đủ dữ liệu Dashboard. Vui lòng tải lại trang hoặc thử lại sau.';
        }

        return view('staff.dashboard', compact('dashboard', 'dashboardError'));
    }
}
