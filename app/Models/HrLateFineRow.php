<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLateFineRow extends Model
{
    protected $table = 'hr_late_fine_rows';

    protected $fillable = [
        'period_month',
        'staff_id',
        'late_minutes',
        'allowance_minutes',
        'fine_per_minute',
        'absent_dates',
        'absent_days',
        'absent_day_rate',
        'way_amount',
        'ako_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'late_minutes' => 'integer',
            'allowance_minutes' => 'integer',
            'fine_per_minute' => 'integer',
            'absent_days' => 'integer',
            'absent_day_rate' => 'integer',
            'way_amount' => 'float',
            'ako_amount' => 'float',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HrStaff::class, 'staff_id');
    }

    /**
     * Fine minute = max(0, နောက်ကျမိနစ် − အခွင့်အရေး)
     * (no negative display when late is below allowance)
     */
    public function getFineMinutesAttribute(): int
    {
        return max(0, (int) $this->late_minutes - (int) $this->allowance_minutes);
    }

    /**
     * Fine Amount = Fine minute × Rate
     */
    public function getLateFineAmountAttribute(): float
    {
        return (float) ($this->fine_minutes * (int) $this->fine_per_minute);
    }

    /**
     * Absent Fine = Finger Print မနှိပ်သောရက် × 3000
     */
    public function getAbsentFineAmountAttribute(): float
    {
        return (float) ((int) $this->absent_days * (int) $this->absent_day_rate);
    }

    /**
     * Google Sheet: Total Fine = Fine Amount + Absent Fine (no floor at 0)
     */
    public function getTotalFineAttribute(): float
    {
        return round($this->late_fine_amount + $this->absent_fine_amount, 2);
    }

    /**
     * Google Sheet: Total = Total Fine + Way + A Ko
     */
    public function getGrandTotalAttribute(): float
    {
        return round($this->total_fine + (float) $this->way_amount + (float) $this->ako_amount, 2);
    }
}
