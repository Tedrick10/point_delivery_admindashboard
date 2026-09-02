<?php

namespace App\Services;

use App\Models\HrLateFineItem;
use App\Models\HrLateFineRow;
use App\Models\HrOfficeSalaryRow;
use App\Models\HrStaff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HrPayrollService
{
    public function parseMonth(?string $monthParam): Carbon
    {
        try {
            return $monthParam
                ? Carbon::createFromFormat('Y-m', $monthParam, 'Asia/Yangon')->startOfMonth()
                : now('Asia/Yangon')->startOfMonth();
        } catch (\Throwable $e) {
            return now('Asia/Yangon')->startOfMonth();
        }
    }

    /** Normalize ရက်ပျက် text, e.g. "8//24" → "8/24". */
    public function normalizeAbsentDates(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $parts = preg_split('/[\/,\s]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('/', array_values(array_filter($parts, static fn ($part) => $part !== '')));
    }

    /** Count absent days from normalized date list, e.g. "8/9/10" → 3. */
    public function countAbsentDates(?string $value): int
    {
        $normalized = $this->normalizeAbsentDates($value);

        return $normalized === '' ? 0 : count(explode('/', $normalized));
    }

    /**
     * Pull Account Creation (office) + Delivery Man (rider) accounts into hr_staff.
     */
    public function syncStaffFromAccounts(): int
    {
        $synced = 0;

        $officeUsers = User::query()
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin', 'demo_admin'])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 1);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'user_type', 'branch_id']);

        foreach ($officeUsers as $index => $user) {
            $synced += $this->upsertStaffFromUser($user, 'office', $index + 1) ? 1 : 0;
        }

        $riders = User::query()
            ->where('user_type', 'delivery_man')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 1);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'user_type', 'branch_id']);

        foreach ($riders as $index => $user) {
            $synced += $this->upsertStaffFromUser($user, 'rider', $index + 1) ? 1 : 0;
        }

        // Deactivate linked staff whose accounts are gone / inactive
        $activeUserIds = $officeUsers->pluck('id')->merge($riders->pluck('id'))->unique()->values();
        HrStaff::query()
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', $activeUserIds)
            ->update(['status' => 0]);

        return $synced;
    }

    protected function upsertStaffFromUser(User $user, string $group, int $sortOrder): bool
    {
        $name = trim((string) $user->name) ?: (string) $user->username;
        $code = $this->makeUserStaffCode($user, $group);

        $staff = HrStaff::withTrashed()->where('user_id', $user->id)->first();
        if ($staff) {
            if ($staff->trashed()) {
                $staff->restore();
            }
            $staff->fill([
                'name' => $name,
                'staff_group' => $group,
                'branch_id' => $user->branch_id,
                'sort_order' => $sortOrder,
                'status' => 1,
            ]);
            if (! $staff->code) {
                $staff->code = $code;
            }
            $staff->save();

            return true;
        }

        // Avoid unique code collision with legacy rows
        if (HrStaff::withTrashed()->where('code', $code)->exists()) {
            $code = $this->makeStaffCode($code.$user->id);
        }

        HrStaff::create([
            'code' => $code,
            'name' => $name,
            'staff_group' => $group,
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'monthly_salary' => 0,
            'allowance_minutes' => 60,
            'way_rate' => $group === 'rider' ? 1000 : 0,
            'sort_order' => $sortOrder,
            'status' => 1,
        ]);

        return true;
    }

    protected function makeUserStaffCode(User $user, string $group): string
    {
        $prefix = $group === 'rider' ? 'DM' : 'EMP';
        $fromUsername = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $user->username) ?: '');
        if ($fromUsername !== '') {
            return substr($prefix.$fromUsername, 0, 20);
        }

        return $prefix.$user->id;
    }

    public function ensureLateFineRows(Carbon $month, ?string $staffGroup = null): Collection
    {
        $period = $month->toDateString();
        $finePerMinute = $this->defaultFinePerMinute();
        $absentDayRate = $this->defaultAbsentDayRate();
        $staffQuery = HrStaff::query()->active()->orderBy('staff_group')->orderBy('sort_order')->orderBy('name');
        if ($staffGroup) {
            $staffQuery->where('staff_group', $staffGroup);
        }
        $staff = $staffQuery->get();

        foreach ($staff as $member) {
            $row = HrLateFineRow::firstOrCreate(
                ['period_month' => $period, 'staff_id' => $member->id],
                [
                    'late_minutes' => 0,
                    'allowance_minutes' => (int) $member->allowance_minutes,
                    'fine_per_minute' => $finePerMinute,
                    'absent_days' => 0,
                    'absent_day_rate' => $absentDayRate,
                    'way_amount' => 0,
                    'ako_amount' => 0,
                ]
            );

            // Keep Super Admin staff allowance in sync on the monthly sheet.
            if ((int) $row->allowance_minutes !== (int) $member->allowance_minutes) {
                $row->allowance_minutes = (int) $member->allowance_minutes;
                $row->save();
            }
        }

        return HrLateFineRow::query()
            ->with(['staff.user'])
            ->whereDate('period_month', $period)
            ->when($staffGroup, function ($q) use ($staffGroup) {
                $q->whereHas('staff', fn ($s) => $s->where('staff_group', $staffGroup));
            })
            ->get()
            ->sortBy(fn (HrLateFineRow $row) => sprintf(
                '%s-%05d-%s',
                $row->staff?->staff_group === 'rider' ? '1' : '0',
                $row->staff?->sort_order ?? 9999,
                $row->staff?->name ?? ''
            ))
            ->values();
    }

    public function defaultFinePerMinute(): int
    {
        $raw = SettingData('hr_payroll', 'fine_per_minute');
        if ($raw === null || $raw === '') {
            return 100;
        }

        return max(0, (int) $raw);
    }

    public function defaultAbsentDayRate(): int
    {
        $raw = SettingData('hr_payroll', 'absent_day_rate');
        if ($raw === null || $raw === '') {
            return 3000;
        }

        return max(0, (int) $raw);
    }

    public function setDefaultFinePerMinute(int $amount): int
    {
        $amount = max(0, $amount);
        \App\Models\Setting::query()->updateOrCreate(
            ['type' => 'hr_payroll', 'key' => 'fine_per_minute'],
            ['value' => (string) $amount]
        );

        return $amount;
    }

    public function setDefaultAbsentDayRate(int $amount): int
    {
        $amount = max(0, $amount);
        \App\Models\Setting::query()->updateOrCreate(
            ['type' => 'hr_payroll', 'key' => 'absent_day_rate'],
            ['value' => (string) $amount]
        );

        return $amount;
    }

    /**
     * Apply global Late Fine rates to all rows for a month (and optionally all months).
     */
    public function applyGlobalLateFineRates(?Carbon $month = null): int
    {
        $finePerMinute = $this->defaultFinePerMinute();
        $absentDayRate = $this->defaultAbsentDayRate();
        $query = HrLateFineRow::query();
        if ($month) {
            $query->whereDate('period_month', $month->toDateString());
        }

        return $query->update([
            'fine_per_minute' => $finePerMinute,
            'absent_day_rate' => $absentDayRate,
        ]);
    }

    public function updateStaffAllowanceMinutes(HrStaff $staff, int $minutes): HrStaff
    {
        $minutes = max(0, min(600, $minutes));
        $staff->allowance_minutes = $minutes;
        $staff->save();

        HrLateFineRow::query()
            ->where('staff_id', $staff->id)
            ->update(['allowance_minutes' => $minutes]);

        return $staff->fresh();
    }

    public function updateStaffWayRate(HrStaff $staff, float $rate): HrStaff
    {
        $rate = max(0, round($rate, 2));
        $staff->way_rate = $rate;
        $staff->save();

        if ($staff->staff_group === 'rider') {
            HrOfficeSalaryRow::query()
                ->where('staff_id', $staff->id)
                ->update(['way_rate' => $rate]);
        }

        return $staff->fresh();
    }

    public function lateFineItems(Carbon $month): Collection
    {
        return HrLateFineItem::query()
            ->with('staff')
            ->whereDate('period_month', $month->toDateString())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function lateFineTotals(Collection $rows, Collection $items): array
    {
        $itemByStaff = $items->groupBy('staff_id');

        $enriched = $rows->map(function (HrLateFineRow $row) use ($itemByStaff) {
            $extra = (float) ($itemByStaff->get($row->staff_id)?->sum('amount') ?? 0);
            $row->setAttribute('incident_total', $extra);
            // Sheet Total = Total Fine (Way / A Ko removed from Late Time Fine UI)
            $row->setAttribute('sheet_total', $row->total_fine);

            return $row;
        });

        return [
            'rows' => $enriched,
            'sum_late_fine' => round((float) $enriched->sum('late_fine_amount'), 2),
            'sum_absent_fine' => round((float) $enriched->sum('absent_fine_amount'), 2),
            'sum_total_fine' => round((float) $enriched->sum('total_fine'), 2),
            'sum_way' => round((float) $enriched->sum('way_amount'), 2),
            'sum_ako' => round((float) $enriched->sum('ako_amount'), 2),
            'sum_incidents' => round((float) $items->sum('amount'), 2),
            'sum_grand' => round((float) $enriched->sum('total_fine'), 2),
        ];
    }

    public function ensureOfficeSalaryRows(Carbon $month): Collection
    {
        return $this->ensureSalaryRows($month, 'office');
    }

    public function ensureRiderSalaryRows(Carbon $month): Collection
    {
        return $this->ensureSalaryRows($month, 'rider');
    }

    /**
     * @param  'office'|'rider'  $staffGroup
     */
    public function ensureSalaryRows(Carbon $month, string $staffGroup = 'office'): Collection
    {
        $period = $month->toDateString();
        $staff = HrStaff::query()
            ->active()
            ->where('staff_group', $staffGroup)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $lateByStaff = HrLateFineRow::query()
            ->whereDate('period_month', $period)
            ->get()
            ->keyBy('staff_id');

        $incidentByStaff = HrLateFineItem::query()
            ->whereDate('period_month', $period)
            ->get()
            ->groupBy('staff_id');

        foreach ($staff as $member) {
            $lateRow = $lateByStaff->get($member->id);
            $deductions = $this->salaryDeductionsFromLateFine($lateRow, $incidentByStaff->get($member->id));

            $existing = HrOfficeSalaryRow::query()
                ->whereDate('period_month', $period)
                ->where('staff_id', $member->id)
                ->first();

            if (! $existing) {
                HrOfficeSalaryRow::create([
                    'period_month' => $period,
                    'staff_id' => $member->id,
                    'monthly_salary' => $staffGroup === 'office' ? (float) $member->monthly_salary : 0,
                    'salary_day_base' => max(1, $month->daysInMonth - 3),
                    'rest_days' => 0,
                    'way_count' => 0,
                    'way_rate' => $staffGroup === 'rider'
                        ? (float) ($member->way_rate > 0 ? $member->way_rate : 1000)
                        : 0,
                    'late_minute_amount' => $deductions['late_minute_amount'],
                    'fine_amount' => $deductions['fine_amount'],
                    'bag_deduction' => 0,
                    'personal_expense' => 0,
                    'deposit' => 0,
                ]);
            } else {
                // Keep Late Amount / Fine Amount in sync with Late Time Fine sheet
                $existing->late_minute_amount = $deductions['late_minute_amount'];
                $existing->fine_amount = $deductions['fine_amount'];
                if ($staffGroup === 'rider') {
                    $staffWayRate = (float) ($member->way_rate > 0 ? $member->way_rate : 1000);
                    if ((float) $existing->way_rate !== $staffWayRate) {
                        $existing->way_rate = $staffWayRate;
                    }
                }
                $existing->save();
            }
        }

        return HrOfficeSalaryRow::query()
            ->with(['staff.user'])
            ->whereDate('period_month', $period)
            ->whereHas('staff', fn ($q) => $q->where('staff_group', $staffGroup))
            ->get()
            ->sortBy(fn (HrOfficeSalaryRow $row) => sprintf('%05d-%s', $row->staff?->sort_order ?? 9999, $row->staff?->name ?? ''))
            ->values();
    }

    public function syncSalaryDeductionsFromLateFine(Carbon $month, ?string $staffGroup = null): int
    {
        $period = $month->toDateString();
        $rows = HrOfficeSalaryRow::query()
            ->with('staff')
            ->whereDate('period_month', $period)
            ->when($staffGroup, function ($q) use ($staffGroup) {
                $q->whereHas('staff', fn ($s) => $s->where('staff_group', $staffGroup));
            })
            ->get();
        $lateByStaff = HrLateFineRow::query()->whereDate('period_month', $period)->get()->keyBy('staff_id');
        $incidentByStaff = HrLateFineItem::query()->whereDate('period_month', $period)->get()->groupBy('staff_id');
        $updated = 0;

        foreach ($rows as $row) {
            $deductions = $this->salaryDeductionsFromLateFine(
                $lateByStaff->get($row->staff_id),
                $incidentByStaff->get($row->staff_id)
            );
            $row->late_minute_amount = $deductions['late_minute_amount'];
            $row->fine_amount = $deductions['fine_amount'];
            $row->save();
            $updated++;
        }

        return $updated;
    }

    /**
     * Employee List Off → +1 နားရက် on the current month salary sheet (once per Off day).
     */
    public function incrementRestDayForUser(User $user): int
    {
        $this->syncStaffFromAccounts();

        $staff = HrStaff::query()
            ->active()
            ->where('user_id', $user->id)
            ->first();

        if (! $staff) {
            return 0;
        }

        $month = $this->parseMonth(null);
        $group = $staff->staff_group === 'rider' ? 'rider' : 'office';
        $this->ensureSalaryRows($month, $group);

        $row = HrOfficeSalaryRow::query()
            ->whereDate('period_month', $month->toDateString())
            ->where('staff_id', $staff->id)
            ->first();

        if (! $row) {
            return 0;
        }

        $row->rest_days = min(31, (int) $row->rest_days + 1);
        $row->save();

        return (int) $row->rest_days;
    }

    /**
     * Map Late Fine into salary columns.
     * Late Amount = Late Time Fine sheet late Fine Amount (fine_minutes × rate)
     * Fine Amount = Finger Print absent fine + Extra Fine items
     */
    protected function salaryDeductionsFromLateFine(?HrLateFineRow $lateRow, $incidentItems = null): array
    {
        $lateFineAmount = max(0, (float) ($lateRow?->late_fine_amount ?? 0));
        $absentFineAmount = max(0, (float) ($lateRow?->absent_fine_amount ?? 0));
        $extraFineAmount = (float) collect($incidentItems)->sum('amount');

        return [
            'late_minute_amount' => round($lateFineAmount, 2),
            'fine_amount' => round($absentFineAmount + $extraFineAmount, 2),
        ];
    }

    /**
     * Resolve the authenticated user's own payroll salary for a month.
     * Syncs staff + salary rows so data appears automatically in apps/accounts.
     */
    public function salaryForUser(User $user, ?string $monthParam = null): ?array
    {
        $this->syncStaffFromAccounts();

        $staff = HrStaff::query()
            ->active()
            ->where('user_id', $user->id)
            ->first();

        if (! $staff) {
            return null;
        }

        $month = $this->parseMonth($monthParam);
        $group = $staff->staff_group === 'rider' ? 'rider' : 'office';
        $this->ensureLateFineRows($month, $group);
        $this->ensureSalaryRows($month, $group);

        $row = HrOfficeSalaryRow::query()
            ->with(['staff.user'])
            ->whereDate('period_month', $month->toDateString())
            ->where('staff_id', $staff->id)
            ->first();

        if (! $row) {
            return null;
        }

        return $this->serializeSalaryRow($row, $month);
    }

    public function serializeSalaryRow(HrOfficeSalaryRow $row, ?Carbon $month = null): array
    {
        $month = $month ?: Carbon::parse($row->period_month)->startOfMonth();
        $group = ($row->staff?->staff_group ?? 'office') === 'rider' ? 'rider' : 'office';
        $base = [
            'period_month' => $month->toDateString(),
            'month' => $month->format('Y-m'),
            'month_label' => $month->format('F Y'),
            'staff_group' => $group,
            'staff_id' => $row->staff_id,
            'staff_name' => $row->staff?->name,
            'late_minute' => round((float) $row->late_minute_amount, 2),
            'late_amount' => round((float) $row->late_minute_amount, 2),
            'fine_amount' => round((float) $row->fine_amount, 2),
            'bag_deduction' => round((float) $row->bag_deduction, 2),
            'personal_expense' => round((float) $row->personal_expense, 2),
            'deposit' => round((float) $row->deposit, 2),
            'total_salary' => round((float) $row->total_salary, 2),
            'total_deduction' => round((float) $row->total_deduction, 2),
            'net_pay' => round((float) $row->net_pay, 2),
            'notes' => $row->notes,
        ];

        if ($group === 'rider') {
            return array_merge($base, [
                'way_count' => (int) $row->way_count,
                'way_rate' => round((float) $row->way_rate, 2),
                'way_pay' => round((float) $row->way_pay, 2),
            ]);
        }

        return array_merge($base, [
            'monthly_salary' => round((float) $row->monthly_salary, 2),
            'days_in_month' => (int) $row->days_in_month,
            'rest_days' => (int) $row->rest_days,
            'worked_days' => (int) $row->worked_days,
            'day_rate' => round((float) $row->day_rate, 0),
            'basic_salary' => round((float) $row->basic_salary, 2),
        ]);
    }

    public function createStaff(array $data): HrStaff
    {
        return DB::transaction(function () use ($data) {
            $name = trim($data['name']);
            $code = strtoupper(trim((string) ($data['code'] ?? '')));
            if ($code === '') {
                $code = $this->makeStaffCode($name);
            }

            $group = in_array(($data['staff_group'] ?? 'office'), ['office', 'rider'], true)
                ? $data['staff_group']
                : 'office';

            return HrStaff::create([
                'code' => $code,
                'name' => $name,
                'staff_group' => $group,
                'monthly_salary' => (float) ($data['monthly_salary'] ?? 0),
                'allowance_minutes' => (int) ($data['allowance_minutes'] ?? 60),
                'way_rate' => (float) ($data['way_rate'] ?? ($group === 'rider' ? 1000 : 0)),
                'branch_id' => ! empty($data['branch_id']) ? $data['branch_id'] : null,
                'user_id' => ! empty($data['user_id']) ? $data['user_id'] : null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => (int) ($data['status'] ?? 1),
            ]);
        });
    }

    public function makeStaffCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'STAFF');
        $base = substr($base, 0, 12) ?: 'STAFF';
        $code = $base;
        $i = 1;
        while (HrStaff::withTrashed()->where('code', $code)->exists()) {
            $code = substr($base, 0, 10).$i;
            $i++;
        }

        return $code;
    }
}
