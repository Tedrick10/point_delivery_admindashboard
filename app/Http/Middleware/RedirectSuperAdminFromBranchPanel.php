<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Keep Super Admin on the Super Admin panel, but allow operational branch screens.
 */
class RedirectSuperAdminFromBranchPanel
{
    /** @var list<string> */
    protected array $allowedRoutePrefixes = [
        'order.expenses',
        'order.expense-summary',
        'order.money-transfer',
        'order.rider-remit',
        'order.cash-payout',
        'order.os-receive',
        'order.kyo-shin',
        'order.daily-checklist',
        'order.dispatch.',
        'order.index',
        'order.create',
        'order.show',
        'order.edit',
        'order.update',
        'order.destroy',
        'order.dispatch-store',
        // Super Admin–owned HR / account screens (opened from SA panel)
        'hr.late-fine',
        'hr.office-salary',
        'hr.rider-salary',
        'sub-admin',
        'permission',
        'role',
        'home',
        'welcome-promotion',
        'remove.file',
        'ajax-list',
        // Point System (manual credit + follow up confirm)
        'point-system',
        // In-app notification inbox (bell on Super Admin topbar)
        'notification.',
        // Complaint / Inquiry support threads (Complaint → SuperAdmin)
        'customersupport',
        'supportchathistory',
        'inquiry-auto-replies',
        'inquiry.inbox',
        'complaint.inbox',
        // Customer / OS profile opened from support chat
        'users.',
        'users-view.',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && isSuperAdmin(auth()->user())) {
            if ($request->routeIs('super-admin.*') || $request->is('super-admin*')) {
                return $next($request);
            }

            $routeName = (string) $request->route()?->getName();

            // Always allow session logout from the branch panel.
            if ($routeName === 'logout' || $request->is('logout')) {
                return $next($request);
            }

            foreach ($this->allowedRoutePrefixes as $prefix) {
                if ($routeName !== '' && str_starts_with($routeName, $prefix)) {
                    return $next($request);
                }
            }

            return redirect()->route('super-admin.dashboard');
        }

        return $next($request);
    }
}
