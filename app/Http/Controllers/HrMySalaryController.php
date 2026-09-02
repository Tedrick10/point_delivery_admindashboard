<?php

namespace App\Http\Controllers;

use App\Services\HrPayrollService;
use Illuminate\Http\Request;

class HrMySalaryController extends Controller
{
    /**
     * Office account (and linked staff) — view own salary for a month.
     */
    public function index(Request $request, HrPayrollService $service)
    {
        $user = auth()->user();
        if (! $user || ($user->user_type ?? '') === 'client') {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $month = $service->parseMonth($request->get('month'));
        $salary = $service->salaryForUser($user, $month->format('Y-m'));

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
            'monthLabel'
        ));
    }
}
