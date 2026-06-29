<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\StaffDashboardService;

class StaffDashboardController extends Controller
{
    /**
     * UC-STAFF-05: Staff Dashboard foundation.
     */
    public function index(StaffDashboardService $dashboardService)
    {
        $dashboard = $dashboardService->getDailyOverview();

        return view('staff.dashboard', compact('dashboard'));
    }
}
