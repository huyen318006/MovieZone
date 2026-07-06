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
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
        ], [
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'cinema_id.exists' => 'Rạp được chọn không tồn tại.',
            'movie_id.exists' => 'Phim được chọn không tồn tại.',
        ]);

        $filters = $dashboardService->normalizeFilters($validated);
        $filterOptions = $dashboardService->filterOptions();

        try {
            $dashboard = $dashboardService->getOverview($filters);
            $dashboardError = null;
        } catch (\Throwable $e) {
            Log::warning('Admin dashboard load failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'filters' => $filters,
            ]);

            $dashboard = $dashboardService->emptyOverview($filters);
            $dashboardError = 'Không thể tải dữ liệu Dashboard. Vui lòng thử lại sau.';
        }

        return view('admin.dashboard', compact('dashboard', 'dashboardError', 'filterOptions'));
    }
}