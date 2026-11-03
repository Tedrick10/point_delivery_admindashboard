<?php

namespace App\Http\Controllers;

use App\Models\HrStaff;
use App\Models\PointHistory;
use App\Models\PointOrderPayment;
use App\Models\PointSalaryRequest;
use App\Models\PointWallet;
use App\Models\User;
use App\Services\PointOrderPaymentService;
use App\Services\PointSalaryRequestService;
use App\Services\PointWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PointSystemController extends Controller
{
    /**
     * Payload for Super Admin embedded Point System board.
     */
    public static function screenPayload(Request $request): array
    {
        $tab = trim((string) $request->get('tab', 'give'));
        if (! in_array($tab, ['give', 'follow-up', 'salary'], true)) {
            $tab = 'give';
        }

        $q = trim((string) $request->get('q', ''));
        $recipientType = trim((string) $request->get('recipient_type', 'os'));
        if (! in_array($recipientType, ['os', 'staff', 'rider'], true)) {
            // Back-compat with older filter values
            $recipientType = match ($recipientType) {
                'client', 'all' => 'os',
                'delivery_man' => 'rider',
                default => 'os',
            };
        }

        $directory = self::recipientDirectory($recipientType, $q);

        $recentCredits = PointHistory::query()
            ->with(['user:id,name,user_type', 'creator:id,name'])
            ->where('transaction_type', 'admin_credit')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $followStatus = trim((string) $request->get('status', 'all'));
        $followAllowed = [
            'all',
            PointOrderPayment::STATUS_PENDING_ADMIN,
            PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
            PointOrderPayment::STATUS_CONFIRMED,
        ];
        if (! in_array($followStatus, $followAllowed, true)) {
            $followStatus = 'all';
        }

        $followUps = PointOrderPayment::query()
            ->with([
                'user:id,name,user_type',
                'adminApprover:id,name',
                'superAdminConfirmer:id,name',
            ])
            // OS receive Point pay removed — Follow Up shows order bonus payments only.
            ->where(function ($query) {
                $query->where('payment_kind', PointOrderPayment::KIND_ORDER)
                    ->orWhere(function ($inner) {
                        $inner->whereNull('payment_kind')
                            ->whereNull('os_receive_settlement_id');
                    });
            })
            ->where('status', '!=', PointOrderPayment::STATUS_REJECTED)
            ->when($followStatus !== 'all', fn ($query) => $query->where('status', $followStatus))
            ->when($q !== '' && $tab === 'follow-up', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('username', 'like', "%{$q}%")
                            ->orWhere('contact_number', 'like', "%{$q}%");
                    });
                    if (is_numeric($q)) {
                        $inner->orWhere('order_id', (int) $q)
                            ->orWhere('id', (int) $q)
                            ->orWhere('points', (float) $q);
                    }
                });
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $salaryStatus = trim((string) $request->get('salary_status', 'all'));
        $salaryAllowed = ['all', PointSalaryRequest::STATUS_PENDING, PointSalaryRequest::STATUS_APPROVED, PointSalaryRequest::STATUS_REJECTED];
        if (! in_array($salaryStatus, $salaryAllowed, true)) {
            $salaryStatus = 'all';
        }

        $salaryKind = trim((string) $request->get('salary_kind', 'os'));
        if (! in_array($salaryKind, ['os', 'staff', 'rider'], true)) {
            $salaryKind = 'os';
        }

        $applySalaryKind = function ($query) use ($salaryKind) {
            self::applySalaryRequestKindFilter($query, $salaryKind);
        };

        $salaryRequests = PointSalaryRequest::query()
            ->with(['user:id,name,user_type', 'staff:id,name,staff_group', 'approver:id,name'])
            ->when($salaryStatus !== 'all', fn ($query) => $query->where('status', $salaryStatus))
            ->tap($applySalaryKind)
            ->when($q !== '' && $tab === 'salary', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('salary_period', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                                ->orWhere('username', 'like', "%{$q}%")
                                ->orWhere('contact_number', 'like', "%{$q}%");
                        });
                    if (is_numeric($q)) {
                        $inner->orWhere('id', (int) $q)
                            ->orWhere('points', (float) $q)
                            ->orWhere('amount', (float) $q);
                    }
                });
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $salaryKindCounts = [
            'os' => (int) PointSalaryRequest::query()->tap(fn ($q) => self::applySalaryRequestKindFilter($q, 'os'))->count(),
            'staff' => (int) PointSalaryRequest::query()->tap(fn ($q) => self::applySalaryRequestKindFilter($q, 'staff'))->count(),
            'rider' => (int) PointSalaryRequest::query()->tap(fn ($q) => self::applySalaryRequestKindFilter($q, 'rider'))->count(),
        ];

        $salaryTotalsBase = PointSalaryRequest::query()->tap($applySalaryKind);

        return [
            'tab' => $tab,
            'q' => $q,
            'recipientType' => $recipientType,
            'directory' => $directory,
            'directoryCounts' => self::recipientDirectoryCounts(),
            'recentCredits' => $recentCredits,
            'followStatus' => $followStatus,
            'followUps' => $followUps,
            'followPointTotals' => [
                'pending' => (float) PointOrderPayment::query()
                    ->where(function ($query) {
                        $query->where('payment_kind', PointOrderPayment::KIND_ORDER)
                            ->orWhere(function ($inner) {
                                $inner->whereNull('payment_kind')
                                    ->whereNull('os_receive_settlement_id');
                            });
                    })
                    ->whereIn('status', [
                        PointOrderPayment::STATUS_PENDING_ADMIN,
                        PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
                    ])
                    ->sum('points'),
                'confirmed' => (float) PointOrderPayment::query()
                    ->where(function ($query) {
                        $query->where('payment_kind', PointOrderPayment::KIND_ORDER)
                            ->orWhere(function ($inner) {
                                $inner->whereNull('payment_kind')
                                    ->whereNull('os_receive_settlement_id');
                            });
                    })
                    ->where('status', PointOrderPayment::STATUS_CONFIRMED)
                    ->sum('points'),
            ],
            'salaryStatus' => $salaryStatus,
            'salaryKind' => $salaryKind,
            'salaryKindCounts' => $salaryKindCounts,
            'salaryRequests' => $salaryRequests,
            'salaryPointTotals' => [
                'pending' => (float) (clone $salaryTotalsBase)
                    ->where('status', PointSalaryRequest::STATUS_PENDING)
                    ->sum('points'),
                'confirmed' => (float) (clone $salaryTotalsBase)
                    ->where('status', PointSalaryRequest::STATUS_APPROVED)
                    ->sum('points'),
            ],
            'pendingFollowUps' => PointOrderPayment::query()
                ->where(function ($query) {
                    $query->where('payment_kind', PointOrderPayment::KIND_ORDER)
                        ->orWhere(function ($inner) {
                            $inner->whereNull('payment_kind')
                                ->whereNull('os_receive_settlement_id');
                        });
                })
                ->whereIn('status', [
                    PointOrderPayment::STATUS_PENDING_ADMIN,
                    PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
                ])->count(),
            'pendingSalary' => PointSalaryRequest::query()
                ->where('status', PointSalaryRequest::STATUS_PENDING)
                ->count(),
            'searchUsersUrl' => route('point-system.search-users'),
            'creditUrl' => route('point-system.credit'),
        ];
    }

    /**
     * Filter Bonus Requests by audience: OS clients, office Staff, or Riders.
     */
    protected static function applySalaryRequestKindFilter($query, string $kind): void
    {
        if ($kind === 'os') {
            $query->where(function ($inner) {
                $inner->where('request_kind', PointSalaryRequest::KIND_CLIENT)
                    ->orWhereHas('user', fn ($u) => $u->where('user_type', 'client'));
            });

            return;
        }

        if ($kind === 'rider') {
            $query->where(function ($inner) {
                $inner->where(function ($x) {
                    $x->whereNull('request_kind')
                        ->orWhere('request_kind', '!=', PointSalaryRequest::KIND_CLIENT);
                })->whereHas('user', fn ($u) => $u->where('user_type', 'delivery_man'));
            });

            return;
        }

        // Staff (and other non-client / non-rider accounts).
        $query->where(function ($inner) {
            $inner->where(function ($x) {
                $x->whereNull('request_kind')
                    ->orWhere('request_kind', '!=', PointSalaryRequest::KIND_CLIENT);
            })->whereHas('user', function ($u) {
                $u->whereNotIn('user_type', ['client', 'delivery_man']);
            });
        });
    }

    /**
     * Full directory lists for Give Points (not only existing wallets).
     *
     * @return Collection<int, object{id:int,name:?string,username:?string,contact_number:?string,user_type:string,group:string,balance:float,meta:?string}>
     */
    public static function recipientDirectory(string $type, string $q = ''): Collection
    {
        $balances = PointWallet::query()->pluck('balance', 'user_id');

        if ($type === 'os') {
            $rows = User::query()
                ->select(['id', 'name', 'username', 'contact_number', 'user_type', 'approval_status', 'status'])
                ->where('user_type', 'client')
                ->where(function ($query) {
                    $query->where('approval_status', 'approved')
                        ->orWhereNull('approval_status');
                })
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 1)->orWhere('status', '1');
                })
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('name', 'like', "%{$q}%")
                            ->orWhere('username', 'like', "%{$q}%")
                            ->orWhere('contact_number', 'like', "%{$q}%");
                        if (is_numeric($q)) {
                            $inner->orWhere('id', (int) $q);
                        }
                    });
                })
                ->orderBy('name')
                ->limit(200)
                ->get()
                ->map(function (User $user) use ($balances) {
                    return (object) [
                        'id' => (int) $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'contact_number' => $user->contact_number,
                        'user_type' => 'client',
                        'group' => 'os',
                        'balance' => (float) ($balances[$user->id] ?? 0),
                        'meta' => $user->username ?: null,
                    ];
                });

            return $rows->values();
        }

        if ($type === 'rider') {
            $rows = User::query()
                ->select(['id', 'name', 'username', 'contact_number', 'user_type', 'status'])
                ->where('user_type', 'delivery_man')
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 1)->orWhere('status', '1');
                })
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('name', 'like', "%{$q}%")
                            ->orWhere('username', 'like', "%{$q}%")
                            ->orWhere('contact_number', 'like', "%{$q}%");
                        if (is_numeric($q)) {
                            $inner->orWhere('id', (int) $q);
                        }
                    });
                })
                ->orderBy('name')
                ->limit(200)
                ->get()
                ->map(function (User $user) use ($balances) {
                    return (object) [
                        'id' => (int) $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'contact_number' => $user->contact_number,
                        'user_type' => 'delivery_man',
                        'group' => 'rider',
                        'balance' => (float) ($balances[$user->id] ?? 0),
                        'meta' => null,
                    ];
                });

            return $rows->values();
        }

        // Staff = HR office staff with linked user accounts
        $staff = HrStaff::query()
            ->active()
            ->where('staff_group', 'office')
            ->whereNotNull('user_id')
            ->with(['user:id,name,username,contact_number,user_type,status'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                                ->orWhere('username', 'like', "%{$q}%")
                                ->orWhere('contact_number', 'like', "%{$q}%");
                            if (is_numeric($q)) {
                                $u->orWhere('id', (int) $q);
                            }
                        });
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->filter(fn (HrStaff $row) => $row->user)
            ->map(function (HrStaff $row) use ($balances) {
                $user = $row->user;

                return (object) [
                    'id' => (int) $user->id,
                    'name' => $user->name ?: $row->name,
                    'username' => $user->username,
                    'contact_number' => $user->contact_number,
                    'user_type' => (string) $user->user_type,
                    'group' => 'staff',
                    'balance' => (float) ($balances[$user->id] ?? 0),
                    'meta' => trim(($row->code ? $row->code.' · ' : '').$row->name),
                ];
            })
            ->values();

        return $staff;
    }

    public static function recipientDirectoryCounts(): array
    {
        return [
            'os' => (int) User::query()
                ->where('user_type', 'client')
                ->where(function ($query) {
                    $query->where('approval_status', 'approved')->orWhereNull('approval_status');
                })
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 1)->orWhere('status', '1');
                })
                ->count(),
            'staff' => (int) HrStaff::query()
                ->active()
                ->where('staff_group', 'office')
                ->whereNotNull('user_id')
                ->whereHas('user')
                ->count(),
            'rider' => (int) User::query()
                ->where('user_type', 'delivery_man')
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 1)->orWhere('status', '1');
                })
                ->count(),
        ];
    }

    protected function redirectAfterPointAction(Request $request, string $tab, string $flashKey, string $fallbackRoute)
    {
        if ($request->input('embed_return') === 'super-admin' || isSuperAdmin(auth()->user())) {
            $params = array_filter([
                'screen' => 'point-system',
                'tab' => $tab,
                'status' => $tab === 'follow-up' ? $request->input('status') : null,
                'salary_status' => $tab === 'salary' ? $request->input('salary_status') : null,
                'salary_kind' => $tab === 'salary' ? $request->input('salary_kind') : null,
                'q' => filled($request->input('q')) ? $request->input('q') : null,
            ], fn ($v) => $v !== null && $v !== '');

            return redirect()
                ->route('super-admin.screens.show', $params)
                ->withSuccess(__($flashKey));
        }

        return redirect()->route($fallbackRoute)->withSuccess(__($flashKey));
    }

    public function index(Request $request, PointWalletService $points)
    {
        if (! $this->canAccessList()) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $recipientType = trim((string) $request->get('recipient_type', 'all'));
        if (! in_array($recipientType, ['all', 'client', 'delivery_man', 'staff'], true)) {
            $recipientType = 'all';
        }

        $q = trim((string) $request->get('q', ''));

        $wallets = PointWallet::query()
            ->with(['user:id,name,username,contact_number,user_type'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('contact_number', 'like', "%{$q}%")
                        ->orWhere('id', is_numeric($q) ? (int) $q : 0);
                });
            })
            ->when($recipientType === 'client', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('user_type', 'client')))
            ->when($recipientType === 'delivery_man', fn ($query) => $query->whereHas('user', fn ($u) => $u->where('user_type', 'delivery_man')))
            ->when($recipientType === 'staff', function ($query) {
                $query->whereHas('user', function ($u) {
                    $u->where(function ($inner) {
                        $inner->where('user_type', 'admin')
                            ->orWhereHas('hrStaff');
                    });
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(30)
            ->withQueryString();

        $recentCredits = PointHistory::query()
            ->with(['user:id,name,user_type', 'creator:id,name'])
            ->where('transaction_type', 'admin_credit')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $pendingFollowUps = PointOrderPayment::query()
            ->where(function ($query) {
                $query->where('payment_kind', PointOrderPayment::KIND_ORDER)
                    ->orWhere(function ($inner) {
                        $inner->whereNull('payment_kind')
                            ->whereNull('os_receive_settlement_id');
                    });
            })
            ->whereIn('status', [
                PointOrderPayment::STATUS_PENDING_ADMIN,
                PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
            ])
            ->count();

        $pendingSalary = PointSalaryRequest::query()
            ->where('status', PointSalaryRequest::STATUS_PENDING)
            ->count();

        $pageTitle = __('message.point_system');
        $canGive = $this->canGivePoints();
        $assets = [];

        return view('point-system.index', compact(
            'pageTitle',
            'assets',
            'wallets',
            'recentCredits',
            'pendingFollowUps',
            'pendingSalary',
            'recipientType',
            'q',
            'canGive'
        ));
    }

    public function followUp(Request $request)
    {
        if (! $this->canAccessList()) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $status = trim((string) $request->get('status', 'all'));
        $allowed = [
            'all',
            PointOrderPayment::STATUS_PENDING_ADMIN,
            PointOrderPayment::STATUS_PENDING_SUPER_ADMIN,
            PointOrderPayment::STATUS_CONFIRMED,
            PointOrderPayment::STATUS_REJECTED,
        ];
        if (! in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $q = trim((string) $request->get('q', ''));

        $rows = PointOrderPayment::query()
            ->with([
                'user:id,name,username,contact_number,user_type',
                'order:id,status',
                'adminApprover:id,name',
                'superAdminConfirmer:id,name',
            ])
            ->where(function ($query) {
                $query->where('payment_kind', PointOrderPayment::KIND_ORDER)
                    ->orWhere(function ($inner) {
                        $inner->whereNull('payment_kind')
                            ->whereNull('os_receive_settlement_id');
                    });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    if (is_numeric($q)) {
                        $inner->orWhere('order_id', (int) $q)->orWhere('id', (int) $q);
                    }
                    $inner->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('username', 'like', "%{$q}%")
                            ->orWhere('contact_number', 'like', "%{$q}%");
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $pageTitle = __('message.point_system_follow_up');
        $canConfirm = $this->canConfirm();
        $canApprove = $this->canApprove();
        $assets = [];

        return view('point-system.follow-up', compact(
            'pageTitle',
            'assets',
            'rows',
            'status',
            'q',
            'canConfirm',
            'canApprove'
        ));
    }

    public function showPayment(int $id)
    {
        if (! $this->canAccessList()) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $payment = PointOrderPayment::query()
            ->with([
                'user:id,name,username,contact_number,user_type',
                'order',
                'adminApprover:id,name',
                'superAdminConfirmer:id,name',
            ])
            ->findOrFail($id);

        $pageTitle = __('message.point_payment_detail');
        $canConfirm = $this->canConfirm();
        $canApprove = $this->canApprove();
        $assets = [];

        return view('point-system.payment-show', compact(
            'pageTitle',
            'assets',
            'payment',
            'canConfirm',
            'canApprove'
        ));
    }

    public function approvePayment(Request $request, int $id, PointOrderPaymentService $service)
    {
        if (! $this->canApprove()) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $payment = PointOrderPayment::query()->findOrFail($id);
        $data = $request->validate(['remark' => 'nullable|string|max:500']);

        try {
            $service->approveByAdmin($payment, auth()->user(), $data['remark'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return $this->redirectAfterPointAction($request, 'follow-up', 'message.point_payment_approved', 'point-system.follow-up');
    }

    public function confirmPayment(Request $request, int $id, PointOrderPaymentService $service)
    {
        if (! $this->canConfirm()) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $payment = PointOrderPayment::query()->findOrFail($id);
        $data = $request->validate(['remark' => 'nullable|string|max:500']);

        try {
            $service->confirmBySuperAdmin($payment, auth()->user(), $data['remark'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return $this->redirectAfterPointAction($request, 'follow-up', 'message.point_payment_confirmed', 'point-system.follow-up');
    }

    public function rejectPayment(Request $request, int $id, PointOrderPaymentService $service)
    {
        $payment = PointOrderPayment::query()->findOrFail($id);

        $canReject = $this->canConfirm()
            || ($this->canApprove() && $payment->status === PointOrderPayment::STATUS_PENDING_ADMIN);
        if (! $canReject) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $data = $request->validate(['remark' => 'nullable|string|max:500']);

        try {
            $service->reject($payment, auth()->user(), $data['remark'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return $this->redirectAfterPointAction($request, 'follow-up', 'message.point_payment_rejected', 'point-system.follow-up');
    }

    public function salaryRequests(Request $request)
    {
        if (! $this->canAccessList()) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $status = trim((string) $request->get('status', 'all'));
        $allowed = ['all', PointSalaryRequest::STATUS_PENDING, PointSalaryRequest::STATUS_APPROVED, PointSalaryRequest::STATUS_REJECTED];
        if (! in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $kind = trim((string) $request->get('kind', 'os'));
        if (! in_array($kind, ['os', 'staff', 'rider'], true)) {
            $kind = 'os';
        }

        $rows = PointSalaryRequest::query()
            ->with(['user:id,name,user_type', 'staff:id,name,staff_group', 'approver:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->tap(fn ($q) => self::applySalaryRequestKindFilter($q, $kind))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $kindCounts = [
            'os' => (int) PointSalaryRequest::query()->tap(fn ($q) => self::applySalaryRequestKindFilter($q, 'os'))->count(),
            'staff' => (int) PointSalaryRequest::query()->tap(fn ($q) => self::applySalaryRequestKindFilter($q, 'staff'))->count(),
            'rider' => (int) PointSalaryRequest::query()->tap(fn ($q) => self::applySalaryRequestKindFilter($q, 'rider'))->count(),
        ];

        $pageTitle = __('message.point_salary_requests');
        $canConfirm = $this->canConfirm();
        $assets = [];

        return view('point-system.salary-requests', compact(
            'pageTitle',
            'assets',
            'rows',
            'status',
            'kind',
            'kindCounts',
            'canConfirm'
        ));
    }

    public function approveSalaryRequest(Request $request, int $id, PointSalaryRequestService $service)
    {
        if (! $this->canConfirm()) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $row = PointSalaryRequest::query()->findOrFail($id);
        $data = $request->validate(['remark' => 'nullable|string|max:500']);

        try {
            $service->approve($row, auth()->user(), $data['remark'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return $this->redirectAfterPointAction($request, 'salary', 'message.point_salary_approved', 'point-system.salary-requests');
    }

    public function rejectSalaryRequest(Request $request, int $id, PointSalaryRequestService $service)
    {
        if (! $this->canConfirm()) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $row = PointSalaryRequest::query()->findOrFail($id);
        $data = $request->validate(['remark' => 'nullable|string|max:500']);

        try {
            $service->reject($row, auth()->user(), $data['remark'] ?? null);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return $this->redirectAfterPointAction($request, 'salary', 'message.point_salary_rejected', 'point-system.salary-requests');
    }

    public function credit(Request $request, PointWalletService $points)
    {
        if (! $this->canGivePoints()) {
            return redirect()->back()->withErrors(__('message.demo_permission_denied'));
        }

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'embed_return' => 'nullable|string',
            'recipient_type' => 'nullable|string|in:os,staff,rider,all',
            'q' => 'nullable|string|max:200',
        ]);

        $user = User::query()->findOrFail((int) $data['user_id']);

        try {
            $points->assertEligibleRecipient($user);
            $history = $points->credit(
                (int) $user->id,
                (float) $data['amount'],
                'admin_credit',
                (int) auth()->id(),
                $data['description'] ?? __('message.point_admin_credit_default'),
                ['data' => ['source' => 'super_admin_manual']]
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        $message = __('message.point_credited_success', [
            'amount' => number_format((float) $history->amount, 2),
            'name' => $user->name,
            'balance' => number_format((float) $history->balance, 2),
        ]);

        if (($data['embed_return'] ?? '') === 'super-admin' || isSuperAdmin(auth()->user())) {
            $returnParams = array_filter([
                'screen' => 'point-system',
                'tab' => 'give',
                'recipient_type' => $data['recipient_type'] ?? null,
                'q' => filled($data['q'] ?? null) ? $data['q'] : null,
            ], fn ($v) => $v !== null && $v !== '');

            return redirect()
                ->route('super-admin.screens.show', $returnParams)
                ->withSuccess($message);
        }

        return redirect()->back()->withSuccess($message);
    }

    public function searchUsers(Request $request)
    {
        if (! $this->canGivePoints() && ! $this->canAccessList()) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $q = trim((string) $request->get('q', ''));
        $type = trim((string) $request->get('recipient_type', 'all'));

        $users = User::query()
            ->select(['id', 'name', 'username', 'contact_number', 'user_type'])
            ->when(in_array($type, ['client', 'os'], true), function ($query) {
                $query->where('user_type', 'client')
                    ->where(function ($inner) {
                        $inner->where('approval_status', 'approved')->orWhereNull('approval_status');
                    });
            })
            ->when(in_array($type, ['delivery_man', 'rider'], true), fn ($query) => $query->where('user_type', 'delivery_man'))
            ->when($type === 'staff', function ($query) {
                $query->whereHas('hrStaff', fn ($s) => $s->where('staff_group', 'office')->where('status', 1));
            })
            ->when($type === 'all', function ($query) {
                $query->where(function ($inner) {
                    $inner->where(function ($os) {
                        $os->where('user_type', 'client')
                            ->where(function ($a) {
                                $a->where('approval_status', 'approved')->orWhereNull('approval_status');
                            });
                    })
                        ->orWhere('user_type', 'delivery_man')
                        ->orWhereHas('hrStaff', fn ($s) => $s->where('staff_group', 'office')->where('status', 1));
                });
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('contact_number', 'like', "%{$q}%");
                    if (is_numeric($q)) {
                        $inner->orWhere('id', (int) $q);
                    }
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (User $user) {
                $wallet = PointWallet::query()->where('user_id', $user->id)->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'contact_number' => $user->contact_number,
                    'user_type' => $user->user_type,
                    'balance' => (float) ($wallet->balance ?? 0),
                    'label' => sprintf(
                        '#%d %s (%s) — %s pts',
                        $user->id,
                        $user->name,
                        $user->user_type,
                        number_format((float) ($wallet->balance ?? 0), 2)
                    ),
                ];
            });

        return response()->json(['data' => $users]);
    }

    protected function canAccessList(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return isSuperAdmin($user)
            || ($user->user_type ?? '') === 'admin'
            || $user->can('point-system-list')
            || $user->can('point-system');
    }

    protected function canGivePoints(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Requirement: only Super Admin gives points.
        return isSuperAdmin($user);
    }

    protected function canApprove(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return isSuperAdmin($user)
            || $user->can('point-system-edit')
            || (($user->user_type ?? '') === 'admin');
    }

    protected function canConfirm(): bool
    {
        return isSuperAdmin(auth()->user());
    }
}
