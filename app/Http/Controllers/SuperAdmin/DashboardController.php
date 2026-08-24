<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, SuperAdminDashboardService $dashboard)
    {
        $period = $dashboard->periodFromRequest($request);

        return view('super-admin.dashboard', array_merge(
            $dashboard->build($period),
            ['saPeriod' => $period]
        ));
    }
}
