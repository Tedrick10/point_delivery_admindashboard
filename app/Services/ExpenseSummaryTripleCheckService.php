<?php

namespace App\Services;

use App\Models\ExpenseSummaryTripleCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpenseSummaryTripleCheckService
{
    /**
     * @return list<array{key: string, label: string, short: string}>
     */
    public function checkerMeta(): array
    {
        return [
            [
                'key' => ExpenseSummaryTripleCheck::KEY_SUPER_ADMIN,
                'label' => __('message.expense_summary_checker_super_admin'),
                'short' => __('message.expense_summary_checker_super_admin_short'),
            ],
            [
                'key' => ExpenseSummaryTripleCheck::KEY_MA_NOE_NOE,
                'label' => __('message.expense_summary_checker_ma_noe_noe'),
                'short' => __('message.expense_summary_checker_ma_noe_noe_short'),
            ],
            [
                'key' => ExpenseSummaryTripleCheck::KEY_MA_PHYU_SIN,
                'label' => __('message.expense_summary_checker_ma_phyu_sin'),
                'short' => __('message.expense_summary_checker_ma_phyu_sin_short'),
            ],
        ];
    }

    public function checkerKeyForUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if (function_exists('isSuperAdmin') && isSuperAdmin($user)) {
            return ExpenseSummaryTripleCheck::KEY_SUPER_ADMIN;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            (string) ($user->name ?? ''),
            (string) ($user->username ?? ''),
            (string) ($user->email ?? ''),
        ]))));

        if ($haystack === '') {
            return null;
        }

        if (str_contains($haystack, 'noe noe')
            || str_contains($haystack, 'manoenoe')
            || str_contains($haystack, 'noenoe')
        ) {
            return ExpenseSummaryTripleCheck::KEY_MA_NOE_NOE;
        }

        if (str_contains($haystack, 'shwe sin')
            || str_contains($haystack, 'mashwesin')
            || str_contains($haystack, 'shwesin')
            || str_contains($haystack, 'phyu sin')
            || str_contains($haystack, 'maphyusin')
            || str_contains($haystack, 'phyusin')
        ) {
            return ExpenseSummaryTripleCheck::KEY_MA_PHYU_SIN;
        }

        return null;
    }

    public function canConfirm(?User $user): bool
    {
        return $this->checkerKeyForUser($user) !== null;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function checksBetween(string $from, string $to): array
    {
        $rows = ExpenseSummaryTripleCheck::query()
            ->whereBetween('check_date', [$from, $to])
            ->get(['check_date', 'checker_key']);

        $map = [];
        foreach ($rows as $row) {
            $day = $row->check_date?->toDateString();
            if (! $day) {
                continue;
            }
            $map[$day][$row->checker_key] = true;
        }

        return $map;
    }

    /**
     * @param  array<string, array<string, bool>>  $checks
     * @return list<array<string, mixed>>
     */
    public function calendarGrid(Carbon $month, string $selectedFrom, string $selectedTo, array $checks = []): array
    {
        $today = now('Asia/Yangon')->toDateString();
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $monthKey = $month->format('Y-m');
        $days = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $ymd = $cursor->toDateString();
            $dots = [];
            $confirmedCount = 0;
            foreach (ExpenseSummaryTripleCheck::KEYS as $key) {
                $on = ! empty($checks[$ymd][$key]);
                $dots[$key] = $on;
                if ($on) {
                    $confirmedCount++;
                }
            }

            $disabled = $ymd > $today;

            $days[] = [
                'ymd' => $ymd,
                'day' => (int) $cursor->format('j'),
                'in_month' => $cursor->format('Y-m') === $monthKey,
                'disabled' => $disabled,
                'selected' => ! $disabled && $ymd >= $selectedFrom && $ymd <= $selectedTo,
                'today' => $ymd === $today,
                'all_checked' => $confirmedCount === count(ExpenseSummaryTripleCheck::KEYS),
                'dots' => $dots,
            ];
        }

        return $days;
    }

    /**
     * @return array{key: string, dates: list<string>, checks: array<string, array<string, bool>>}
     */
    public function confirmRange(User $user, string $from, string $to): array
    {
        $key = $this->checkerKeyForUser($user);
        if (! $key) {
            abort(403, __('message.expense_summary_confirm_denied'));
        }

        $start = Carbon::parse($from, 'Asia/Yangon')->startOfDay();
        $end = Carbon::parse($to, 'Asia/Yangon')->startOfDay();
        $today = now('Asia/Yangon')->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        if ($end->gt($today)) {
            $end = $today->copy();
        }
        if ($start->gt($today)) {
            return [
                'key' => $key,
                'dates' => [],
                'checks' => [],
            ];
        }
        if ($start->diffInDays($end) > 62) {
            $end = $start->copy()->addDays(62);
        }

        $now = now('Asia/Yangon');
        $dates = [];
        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $ymd = $cursor->toDateString();
            $dates[] = $ymd;
            ExpenseSummaryTripleCheck::query()->firstOrCreate(
                [
                    'check_date' => $ymd,
                    'checker_key' => $key,
                ],
                [
                    'user_id' => $user->id,
                    'confirmed_at' => $now,
                ]
            );
        }

        return [
            'key' => $key,
            'dates' => $dates,
            'checks' => $this->checksBetween($start->toDateString(), $end->toDateString()),
        ];
    }

    /**
     * @param  array<string, array<string, bool>>  $checks
     */
    public function selectedAlreadyConfirmed(string $from, string $to, string $checkerKey, array $checks): bool
    {
        $start = Carbon::parse($from, 'Asia/Yangon')->startOfDay();
        $end = Carbon::parse($to, 'Asia/Yangon')->startOfDay();
        $today = now('Asia/Yangon')->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        if ($end->gt($today)) {
            $end = $today->copy();
        }
        if ($start->gt($today)) {
            return true;
        }

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            if (empty($checks[$cursor->toDateString()][$checkerKey])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Collection<int, string>
     */
    public function weekdayLabels(): Collection
    {
        return collect([
            __('message.expense_summary_cal_sun'),
            __('message.expense_summary_cal_mon'),
            __('message.expense_summary_cal_tue'),
            __('message.expense_summary_cal_wed'),
            __('message.expense_summary_cal_thu'),
            __('message.expense_summary_cal_fri'),
            __('message.expense_summary_cal_sat'),
        ]);
    }
}
