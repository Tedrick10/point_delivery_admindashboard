<?php

namespace App\Http\Controllers;

use App\Services\AdminLiveStateService;
use Illuminate\Http\Request;

class AdminLiveController extends Controller
{
    public function __construct(protected AdminLiveStateService $live)
    {
    }

    protected function releaseSessionLock(): void
    {
        if (session()->isStarted()) {
            session()->save();
        }
        // Ensure concurrent AJAX navigations are not blocked by live polling.
        if (function_exists('session_write_close')) {
            @session_write_close();
        }
    }

    /**
     * Global live state: sidebar badges, notifications, optional dashboard stats.
     */
    public function state(Request $request)
    {
        $this->releaseSessionLock();

        $user = auth()->user();
        $page = (string) $request->input('page', '');

        $payload = [
            'status' => true,
            'badges' => $this->live->sidebarCounts($user),
            'notifications' => [
                'counts' => $this->live->notificationUnread($user),
            ],
            'version' => $this->live->pageVersion($page !== '' ? $page : 'global', $user, $request->except(['page', 'include_dashboard', '_'])),
        ];

        if ($page === 'home' || $page === 'dashboard' || $request->boolean('include_dashboard')) {
            $payload['dashboard'] = $this->live->dashboardStats($request);
        }

        return response()->json($payload);
    }

    /**
     * Lightweight page fingerprint for list/detail soft refresh.
     */
    public function pageVersion(Request $request)
    {
        $this->releaseSessionLock();

        $page = (string) $request->input('page', $request->input('page_key', ''));
        if ($page === '') {
            $page = (string) (optional($request->route())->getName() ?? 'global');
        }

        $context = $request->except(['page', 'page_key', '_']);

        return response()->json([
            'status' => true,
            'version' => $this->live->pageVersion($page, auth()->user(), $context),
            'page' => $page,
        ]);
    }
}
