<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    /**
     * UC-ADM-10: Dashboard thống kê Admin.
     */
    public function index(Request $request, AdminDashboardService $dashboardService)
    {
        try {
            $dashboard = $dashboardService->emptyOverview();
            $dashboardError = null;
        } catch (\Throwable $e) {
            Log::warning('Admin dashboard load failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            $dashboard = $dashboardService->emptyOverview();
            $dashboardError = 'Không thể tải dữ liệu Dashboard. Vui lòng thử lại sau.';
        }

        return view('admin.dashboard', compact('dashboard', 'dashboardError'));
    }
}