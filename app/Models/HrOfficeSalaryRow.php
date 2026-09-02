<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrOfficeSalaryRow extends Model
{
    protected $table = 'hr_office_salary_rows';

    protected $fillable = [
        'period_month',
        'staff_id',
        'monthly_salary',
        'salary_day_base',
        'rest_days',
        'way_count',
        'way_rate',
        'late_minute_amount',
        'fine_amount',
        'bag_deduction',
        'personal_expense',
        'deposit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'monthly_salary' => 'float',
            'salary_day_base' => 'integer',
            'rest_days' => 'integer',
            'way_count' => 'integer',
            'way_rate' => 'float',
            'late_minute_amount' => 'float',
            'fine_amount' => 'float',
            'bag_deduction' => 'float',
            'personal_expense' => 'float',
            'deposit' => 'float',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HrStaff::class, 'staff_id');
    }

    public function getDaysInMonthAttribute(): int
    {
        return Carbon::parse($this->period_month)->daysInMonth;
    }

    /**
     * Google Sheet Office: 1 ရက်စာ = လစာ ÷ (ရှိသည့်ရက် − 3)
     * e.g. 30-day month → /27, 31-day month → /28
     */
    public function getDayRateAttribute(): float
    {
        $base = max(1, $this->days_in_month - 3);

        return (float) $this->monthly_salary / $base;
    }

    /**
     * ဆင်းခဲ့သည့်ရက် — month-to-date for the current month (Asia/Yangon),
     * full month for past periods, 0 for future periods.
     * Formula: elapsed calendar days − နားရက်
     */
    public function getWorkedDaysAttribute(): int
    {
        $period = Carbon::parse($this->period_month, 'Asia/Yangon')->startOfMonth();
        $today = Carbon::now('Asia/Yangon')->startOfDay();
        $daysInMonth = $period->daysInMonth;
        $rest = (int) $this->rest_days;

        if ($today->lt($period)) {
            $elapsed = 0;
        } elseif ($today->format('Y-m') === $period->format('Y-m')) {
            $elapsed = min((int) $today->day, $daysInMonth);
        } else {
            $elapsed = $daysInMonth;
        }

        return max(0, $elapsed - $rest);
    }

    /**
     * Google Sheet: Basic = 1 ရက်စာ × ဆင်းခဲ့သည့်ရက်
     */
    public function getBasicSalaryAttribute(): float
    {
        return round($this->day_rate * $this->worked_days, 2);
    }

    /**
     * Google Sheet: Way လစာ = way × 1 way စာ
     */
    public function getWayPayAttribute(): float
    {
        return round((int) $this->way_count * (float) $this->way_rate, 2);
    }

    /**
     * Office: Total လစာ = Basic (way columns removed from Office UI)
     * Rider:  Total လစာ = Way လစာ only
     */
    public function getTotalSalaryAttribute(): float
    {
        if (($this->staff?->staff_group ?? 'office') === 'rider') {
            return $this->way_pay;
        }

        return $this->basic_salary;
    }

    /**
     * Total နှုတ်ငွေ = Late Minute + Fine Amount + bag + Deposit
     */
    public function getTotalDeductionAttribute(): float
    {
        return round(
            (float) $this->late_minute_amount
            + (float) $this->fine_amount
            + (float) $this->bag_deduction
            + (float) $this->deposit,
            2
        );
    }

    /**
     * Google Sheet: နှုတ်ပြီးကျန်ငွေ = Total လစာ − Total နှုတ်ငွေ
     */
    public function getNetPayAttribute(): float
    {
        return round($this->total_salary - $this->total_deduction, 2);
    }
}
