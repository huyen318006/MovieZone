<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

class StaffDashboardController extends Controller
{
    /**
     * UC-STAFF-05: Staff Dashboard foundation.
     */
    public function index()
    {
        return view('staff.dashboard');
    }
}
