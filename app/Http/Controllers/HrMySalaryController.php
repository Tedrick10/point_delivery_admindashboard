<?php

namespace App\Http\Controllers;

use App\Models\PointSalaryRequest;
use App\Services\HrPayrollService;
use App\Services\PointSalaryRequestService;
use App\Services\PointWalletService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class HrMySalaryController extends Controller
{
    /**
     * Office account (and linked staff) — view own salary for a month.
     */
    public function index(Request $request, HrPayrollService $service, PointWalletService $points)
    {
        $user = auth()->user();
        if (! $user || ($user->user_type ?? '') === 'client') {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $month = $service->parseMonth($request->get('month'));
        $salary = $service->salaryForUser($user, $month->format('Y-m'));
        $pointBalance = $points->balance((int) $user->id);
        $pointRequests = PointSalaryRequest::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $pageTitle = __('message.hr_my_salary_title');
        $assets = [];
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        $monthValue = $month->format('Y-m');
        $monthLabel = $month->format('F Y');

        return view('hr.my-salary', compact(
            'pageTitle',
            'assets',
            'salary',
            'prevMonth',
            'nextMonth',
            'monthValue',
            'monthLabel',
            'pointBalance',
            'pointRequests'
        ));
    }

    public function requestPoints(Request $request, PointSalaryRequestService $service)
    {
        $user = auth()->user();
        if (! $user || ($user->user_type ?? '') === 'client') {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $data = $request->validate([
            'points' => 'required|numeric|min:0.01',
            'month' => 'nullable|string',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $service->create(
                $user,
                (float) $data['points'],
                $data['month'] ?? null,
                $data['remark'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()
            ->route('hr.my-salary.index', ['month' => $data['month'] ?? null])
            ->withSuccess(__('message.point_salary_submitted'));
    }
}
