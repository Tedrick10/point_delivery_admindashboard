<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLateFineItem extends Model
{
    protected $table = 'hr_late_fine_items';

    protected $fillable = [
        'period_month',
        'staff_id',
        'staff_code',
        'description',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'amount' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HrStaff::class, 'staff_id');
    }
}
