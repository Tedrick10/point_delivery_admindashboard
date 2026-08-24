<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScreenController extends Controller
{
    public function hub(Request $request, SuperAdminDashboardService $dashboard): View
    {
        $period = $dashboard->periodFromRequest($request);
        $screens = config('super_admin_screens', []);

        return view('super-admin.screens.hub', [
            'screens' => $screens,
            'monthLabel' => $period['label'],
            'saPeriod' => $period,
        ]);
    }

    public function show(Request $request, string $screen, SuperAdminDashboardService $dashboard): View
    {
        $screens = config('super_admin_screens', []);
        if (! isset($screens[$screen])) {
            abort(404);
        }

        $period = $dashboard->periodFromRequest($request);
        $ctx = $dashboard->screenContext($screen, $period);

        return view('super-admin.screens.show', [
            'screenKey' => $screen,
            'screen' => $screens[$screen],
            'metrics' => $ctx['metrics'],
            'branchRows' => $ctx['branchRows'],
            'monthLabel' => $ctx['monthLabel'],
            'saPeriod' => $period,
        ]);
    }
}
