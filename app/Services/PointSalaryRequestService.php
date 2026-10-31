<?php

namespace App\Services;

use App\Models\HrOfficeSalaryRow;
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
        protected HrPayrollService $payroll
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

        $staff = HrStaff::query()->active()->where('user_id', $user->id)->first();
        if (! $staff && ($user->user_type ?? '') !== 'delivery_man' && ($user->user_type ?? '') !== 'admin') {
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

        $request = DB::transaction(function () use ($user, $staff, $pointsAmount, $amount, $period, $remark) {
            $row = PointSalaryRequest::create([
                'user_id' => (int) $user->id,
                'hr_staff_id' => $staff?->id,
                'points' => round($pointsAmount, 2),
                'amount' => $amount,
                'status' => PointSalaryRequest::STATUS_PENDING,
                'salary_period' => $period,
                'remark' => $remark,
            ]);

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

            return $row;
        });

        $this->notifySuperAdminsPending($request->fresh(['user', 'staff']));

        return $request;
    }

    public function approve(PointSalaryRequest $request, User $superAdmin, ?string $remark = null): PointSalaryRequest
    {
        if ($request->status !== PointSalaryRequest::STATUS_PENDING) {
            throw new InvalidArgumentException(__('message.point_salary_invalid_status'));
        }

        return DB::transaction(function () use ($request, $superAdmin, $remark) {
            $staff = $request->hr_staff_id
                ? HrStaff::query()->find($request->hr_staff_id)
                : HrStaff::query()->active()->where('user_id', $request->user_id)->first();

            if (! $staff) {
                throw new InvalidArgumentException(__('message.point_salary_staff_required'));
            }

            $month = $this->payroll->parseMonth($request->salary_period);
            $group = $staff->staff_group === 'rider' ? 'rider' : 'office';
            $this->payroll->ensureLateFineRows($month, $group);
            $this->payroll->ensureSalaryRows($month, $group);

            $salaryRow = HrOfficeSalaryRow::query()
                ->whereDate('period_month', $month->toDateString())
                ->where('staff_id', $staff->id)
                ->lockForUpdate()
                ->first();

            if (! $salaryRow) {
                throw new InvalidArgumentException(__('message.hr_my_salary_not_found'));
            }

            $salaryRow->point_salary_amount = round((float) $salaryRow->point_salary_amount + (float) $request->amount, 2);
            $salaryRow->save();

            $request->status = PointSalaryRequest::STATUS_APPROVED;
            $request->approved_by = (int) $superAdmin->id;
            $request->approved_at = now();
            $request->salary_row_id = $salaryRow->id;
            $request->hr_staff_id = $staff->id;
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
            $this->points->credit(
                (int) $request->user_id,
                (float) $request->points,
                'salary_request_refund',
                (int) $actor->id,
                __('message.point_salary_request_refund'),
                [
                    'reference_id' => $request->id,
                    'reference_type' => PointSalaryRequest::class,
                ]
            );

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

        $this->push->notifyUser(
            $user,
            'point_salary_approved',
            __('message.point_noti_salary_approved_title'),
            __('message.point_noti_salary_approved_body', [
                'amount' => formatPointNotifyNumber($request->amount),
            ]),
            [
                'id' => 'POINT_SAL_'.$request->id,
                'point_salary_request_id' => (string) $request->id,
            ]
        );
    }
}
