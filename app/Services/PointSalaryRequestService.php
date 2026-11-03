<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\HrStaff;
use App\Models\PointSalaryRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PointSalaryRequestService
{
    public function __construct(
        protected PointWalletService $points,
        protected AppPushService $push,
        protected HrPayrollService $payroll,
        protected OsSettlementService $osSettlement
    ) {}

    public function conversionRate(): float
    {
        $rate = SettingData('POINT', 'TO_MMK_RATE');
        $rate = is_numeric($rate) ? (float) $rate : 1.0;

        return $rate > 0 ? $rate : 1.0;
    }

    public function create(User $user, float $pointsAmount, ?string $month = null, ?string $remark = null): PointSalaryRequest
    {
        if ($pointsAmount <= 0) {
            throw new InvalidArgumentException(__('message.point_amount_invalid'));
        }

        $isClient = ($user->user_type ?? '') === 'client';
        $staff = HrStaff::query()->active()->where('user_id', $user->id)->first();
        if (! $isClient && ! $staff && ($user->user_type ?? '') !== 'delivery_man' && ($user->user_type ?? '') !== 'admin') {
            throw new InvalidArgumentException(__('message.point_salary_staff_required'));
        }

        if ($this->points->balance((int) $user->id) < $pointsAmount) {
            throw new InvalidArgumentException(__('message.point_insufficient_balance'));
        }

        $pending = PointSalaryRequest::query()
            ->where('user_id', $user->id)
            ->where('status', PointSalaryRequest::STATUS_PENDING)
            ->exists();
        if ($pending) {
            throw new InvalidArgumentException(__('message.point_salary_pending_exists'));
        }

        $period = $this->payroll->parseMonth($month)->format('Y-m');
        $amount = round($pointsAmount * $this->conversionRate(), 2);
        $kind = $isClient ? PointSalaryRequest::KIND_CLIENT : PointSalaryRequest::KIND_STAFF;

        $request = DB::transaction(function () use ($user, $staff, $pointsAmount, $amount, $period, $remark, $kind, $isClient) {
            $row = PointSalaryRequest::create([
                'user_id' => (int) $user->id,
                'request_kind' => $kind,
                'hr_staff_id' => $staff?->id,
                'points' => round($pointsAmount, 2),
                'amount' => $amount,
                // Staff/Rider: hold immediately.
                // OS client: hold when Os Pay is settled from Os ဆီသို့လွှဲရန် (or on Confirm).
                'points_held' => ! $isClient,
                'os_pay_settled_amount' => 0,
                'os_pay_settled_item_ids' => [],
                'os_pay_settled_details' => [],
                'points_remaining' => round($pointsAmount, 2),
                'status' => PointSalaryRequest::STATUS_PENDING,
                'salary_period' => $period,
                'remark' => $remark,
            ]);

            if (! $isClient) {
                $this->points->debit(
                    (int) $user->id,
                    $pointsAmount,
                    'salary_request',
                    (int) $user->id,
                    __('message.point_salary_request_hold'),
                    [
                        'reference_id' => $row->id,
                        'reference_type' => PointSalaryRequest::class,
                        'data' => ['salary_period' => $period],
                    ]
                );
            }

            return $row;
        });

        $this->notifySuperAdminsPending($request->fresh(['user', 'staff']));

        return $request;
    }

    /**
     * From Os ဆီသို့လွှဲရန်: hold request points (if needed) and settle unpaid Os Pay parcels.
     *
     * @param  list<int>|null  $limitItemIds  Optional parcel filter (sheet row item ids).
     */
    public function settleOsPayFromPayToOs(
        PointSalaryRequest $request,
        User $actor,
        ?array $limitItemIds = null
    ): PointSalaryRequest {
        if (! $request->isClientRequest()) {
            throw new InvalidArgumentException(__('message.point_salary_invalid_status'));
        }
        if ($request->status !== PointSalaryRequest::STATUS_PENDING) {
            throw new InvalidArgumentException(__('message.point_salary_invalid_status'));
        }

        return DB::transaction(function () use ($request, $actor, $limitItemIds) {
            $request = PointSalaryRequest::query()->lockForUpdate()->findOrFail($request->id);

            $this->ensureClientPointsHeld($request, $actor);

            $budget = round(max(0, (float) $request->points - (float) ($request->os_pay_settled_amount ?? 0)), 2);
            if ($budget <= 0) {
                throw new InvalidArgumentException(__('message.point_os_pay_no_budget'));
            }

            $settled = $this->settleOsPayWithPoints($request, $budget, $limitItemIds);
            if ($settled['amount'] <= 0) {
                throw new InvalidArgumentException(__('message.point_os_pay_nothing_to_settle'));
            }

            $prevDetails = is_array($request->os_pay_settled_details) ? $request->os_pay_settled_details : [];
            $prevIds = is_array($request->os_pay_settled_item_ids) ? $request->os_pay_settled_item_ids : [];

            $request->os_pay_settled_amount = round((float) $request->os_pay_settled_amount + $settled['amount'], 2);
            $request->os_pay_settled_item_ids = array_values(array_unique(array_merge(
                array_map('intval', $prevIds),
                $settled['item_ids']
            )));
            $request->os_pay_settled_details = array_values(array_merge($prevDetails, $settled['details']));
            $request->points_remaining = round(max(0, (float) $request->points - (float) $request->os_pay_settled_amount), 2);
            $request->save();

            return $request->fresh();
        });
    }

    public function approve(PointSalaryRequest $request, User $superAdmin, ?string $remark = null): PointSalaryRequest
    {
        if ($request->status !== PointSalaryRequest::STATUS_PENDING) {
            throw new InvalidArgumentException(__('message.point_salary_invalid_status'));
        }

        return DB::transaction(function () use ($request, $superAdmin, $remark) {
            $request = PointSalaryRequest::query()->lockForUpdate()->findOrFail($request->id);

            $staff = $request->hr_staff_id
                ? HrStaff::query()->find($request->hr_staff_id)
                : HrStaff::query()->active()->where('user_id', $request->user_id)->first();

            if ($request->isClientRequest()) {
                // Hold full request if Os Pay was never settled from pay-to-OS yet.
                $this->ensureClientPointsHeld($request, $superAdmin);

                // Auto-settle any remaining unpaid Os Pay before finalizing.
                $budget = round(max(0, (float) $request->points - (float) ($request->os_pay_settled_amount ?? 0)), 2);
                if ($budget > 0) {
                    $settled = $this->settleOsPayWithPoints($request, $budget, null);
                    if ($settled['amount'] > 0) {
                        $prevDetails = is_array($request->os_pay_settled_details) ? $request->os_pay_settled_details : [];
                        $prevIds = is_array($request->os_pay_settled_item_ids) ? $request->os_pay_settled_item_ids : [];
                        $request->os_pay_settled_amount = round((float) $request->os_pay_settled_amount + $settled['amount'], 2);
                        $request->os_pay_settled_item_ids = array_values(array_unique(array_merge(
                            array_map('intval', $prevIds),
                            $settled['item_ids']
                        )));
                        $request->os_pay_settled_details = array_values(array_merge($prevDetails, $settled['details']));
                    }
                }

                // Os Pay portion stays deducted; refund unused remainder to User App.
                $remaining = round(max(0, (float) $request->points - (float) ($request->os_pay_settled_amount ?? 0)), 2);
                $request->points_remaining = $remaining;
                if ($remaining > 0) {
                    $this->points->credit(
                        (int) $request->user_id,
                        $remaining,
                        'os_bonus_remainder_refund',
                        (int) $superAdmin->id,
                        __('message.point_os_bonus_remainder_refund'),
                        [
                            'reference_id' => $request->id,
                            'reference_type' => PointSalaryRequest::class,
                            'data' => [
                                'requested' => (float) $request->points,
                                'os_pay_settled' => (float) $request->os_pay_settled_amount,
                                'refunded' => $remaining,
                            ],
                        ]
                    );
                }
            }

            $request->status = PointSalaryRequest::STATUS_APPROVED;
            $request->approved_by = (int) $superAdmin->id;
            $request->approved_at = now();
            if ($staff) {
                $request->hr_staff_id = $staff->id;
            }
            if ($remark !== null && $remark !== '') {
                $request->remark = trim(($request->remark ? $request->remark."\n" : '').$remark);
            }
            $request->save();

            $this->notifyUserApproved($request->fresh(['user']));

            return $request;
        });
    }

    public function reject(PointSalaryRequest $request, User $actor, ?string $remark = null): PointSalaryRequest
    {
        if ($request->status !== PointSalaryRequest::STATUS_PENDING) {
            throw new InvalidArgumentException(__('message.point_salary_invalid_status'));
        }

        return DB::transaction(function () use ($request, $actor, $remark) {
            $request = PointSalaryRequest::query()->lockForUpdate()->findOrFail($request->id);

            // Refund unused held points (Os Pay settled portion stays deducted).
            if ($request->points_held) {
                $refund = $request->isClientRequest()
                    ? round(max(0, (float) $request->points - (float) ($request->os_pay_settled_amount ?? 0)), 2)
                    : (float) $request->points;

                if ($refund > 0) {
                    $this->points->credit(
                        (int) $request->user_id,
                        $refund,
                        'salary_request_refund',
                        (int) $actor->id,
                        __('message.point_salary_request_refund'),
                        [
                            'reference_id' => $request->id,
                            'reference_type' => PointSalaryRequest::class,
                        ]
                    );
                }
            }

            $request->status = PointSalaryRequest::STATUS_REJECTED;
            $request->rejected_by = (int) $actor->id;
            $request->rejected_at = now();
            if ($remark !== null && $remark !== '') {
                $request->remark = trim(($request->remark ? $request->remark."\n" : '').$remark);
            }
            $request->save();

            return $request;
        });
    }

    /**
     * Hold the full requested points for an OS client (once).
     */
    protected function ensureClientPointsHeld(PointSalaryRequest $request, User $actor): void
    {
        if (! $request->isClientRequest() || $request->points_held) {
            return;
        }

        if ($this->points->balance((int) $request->user_id) < (float) $request->points) {
            throw new InvalidArgumentException(__('message.point_insufficient_balance'));
        }

        $this->points->debit(
            (int) $request->user_id,
            (float) $request->points,
            'os_bonus_hold',
            (int) $actor->id,
            __('message.point_os_bonus_hold'),
            [
                'reference_id' => $request->id,
                'reference_type' => PointSalaryRequest::class,
            ]
        );
        $request->points_held = true;
        $request->points_remaining = round((float) $request->points - (float) ($request->os_pay_settled_amount ?? 0), 2);
        $request->save();
    }

    /**
     * Apply held bonus points against unpaid Os Pay parcels.
     *
     * @param  list<int>|null  $limitItemIds
     * @return array{amount: float, item_ids: list<int>, details: list<array<string,mixed>>}
     */
    public function settleOsPayWithPoints(
        PointSalaryRequest $request,
        ?float $budget = null,
        ?array $limitItemIds = null
    ): array {
        $osId = (int) $request->user_id;
        $budget = round($budget ?? (float) $request->points, 2);
        if ($budget <= 0) {
            return ['amount' => 0.0, 'item_ids' => [], 'details' => []];
        }

        $limit = null;
        if (is_array($limitItemIds) && $limitItemIds !== []) {
            $limit = array_values(array_unique(array_map('intval', $limitItemIds)));
        }

        $today = yangonSettlementDefaultDate('Y-m-d');
        $fromDay = now('Asia/Yangon')->subDays(45)->toDateString();
        $items = $this->osSettlement
            ->completedItemsForPeriod($osId, $fromDay, $today, null)
            ->filter(function ($item) use ($limit) {
                if ($limit !== null && ! in_array((int) $item->id, $limit, true)) {
                    return false;
                }

                return dispatchDeliPayLabel($item) === 'Os Pay';
            })
            ->sortBy('id')
            ->values();

        $settledAmount = 0.0;
        $settledIds = [];
        $details = [];

        foreach ($items as $item) {
            if ($budget <= 0) {
                break;
            }

            /** @var DispatchOrderItem $item */
            $deli = round((float) ($item->deli_amount ?? 0), 2);
            $alreadyPaid = round((float) ($item->os_paid ?? 0), 2);
            $unpaid = round(max(0, $deli - $alreadyPaid), 2);
            if ($unpaid <= 0) {
                continue;
            }

            $apply = min($budget, $unpaid);
            $newOsPaid = round($alreadyPaid + $apply, 2);
            $itemValue = (float) ($item->item_value ?? 0);
            $computed = DispatchOrderItem::computeAmounts(
                $itemValue,
                $deli,
                (float) ($item->advance_paid ?? 0),
                $newOsPaid,
                'os'
            );

            $item->os_paid = $newOsPaid;
            $item->os_to_pay = $computed['os_to_pay'];
            $item->cust_get = $computed['cust_get'];
            if ($newOsPaid + 0.00001 >= $deli && $deli > 0) {
                $item->pickup_pay_mode = 'pay_done';
            }
            $item->os_pay_point_note = __('message.point_os_pay_settled_note');
            $item->save();

            $settledAmount = round($settledAmount + $apply, 2);
            $settledIds[] = (int) $item->id;
            $details[] = [
                'item_id' => (int) $item->id,
                'customer' => (string) ($item->customer_name ?: '-'),
                'phone' => (string) ($item->customer_phone ?: '-'),
                'deli_amount' => $deli,
                'settled' => $apply,
                'item_value' => $itemValue,
            ];
            $budget = round($budget - $apply, 2);
        }

        return ['amount' => $settledAmount, 'item_ids' => $settledIds, 'details' => $details];
    }

    /**
     * Unpaid Os Pay total for an Online Shop's unfinished settlement parcels.
     */
    public function unpaidOsPayDue(int $osId, ?array $limitItemIds = null): float
    {
        $today = yangonSettlementDefaultDate('Y-m-d');
        $fromDay = now('Asia/Yangon')->subDays(45)->toDateString();
        $limit = is_array($limitItemIds) && $limitItemIds !== []
            ? array_values(array_unique(array_map('intval', $limitItemIds)))
            : null;

        return round(
            (float) $this->osSettlement
                ->completedItemsForPeriod($osId, $fromDay, $today, null)
                ->filter(function ($item) use ($limit) {
                    if ($limit !== null && ! in_array((int) $item->id, $limit, true)) {
                        return false;
                    }

                    return dispatchDeliPayLabel($item) === 'Os Pay';
                })
                ->sum(function ($item) {
                    $deli = (float) ($item->deli_amount ?? 0);
                    $paid = (float) ($item->os_paid ?? 0);

                    return max(0, $deli - $paid);
                }),
            2
        );
    }

    protected function notifySuperAdminsPending(PointSalaryRequest $request): void
    {
        $subject = __('message.point_noti_salary_title');
        $message = __('message.point_noti_salary_body', [
            'name' => optional($request->user)->name,
            'points' => formatPointNotifyNumber($request->points),
            'amount' => formatPointNotifyNumber($request->amount),
        ]);

        foreach (getSuperAdminUsers() as $sa) {
            $this->push->notifyUser(
                $sa,
                'point_salary_pending',
                $subject,
                $message,
                [
                    'id' => 'POINT_SAL_'.$request->id,
                    'category' => 'points',
                    'point_salary_request_id' => (string) $request->id,
                ]
            );
        }
    }

    protected function notifyUserApproved(PointSalaryRequest $request): void
    {
        $user = $request->user;
        if (! $user) {
            return;
        }

        $bodyKey = $request->isClientRequest()
            ? 'point_noti_os_bonus_confirmed_body'
            : 'point_noti_salary_approved_body';

        $this->push->notifyUser(
            $user,
            'point_salary_approved',
            __('message.point_noti_salary_approved_title'),
            __("message.{$bodyKey}", [
                'amount' => formatPointNotifyNumber($request->amount),
                'os_pay' => formatPointNotifyNumber($request->os_pay_settled_amount ?? 0),
                'remaining' => formatPointNotifyNumber($request->points_remaining ?? 0),
            ]),
            [
                'id' => 'POINT_SAL_'.$request->id,
                'point_salary_request_id' => (string) $request->id,
            ]
        );
    }
}
