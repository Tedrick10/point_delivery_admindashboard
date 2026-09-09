<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrStaff extends Model
{
    use SoftDeletes;

    protected $table = 'hr_staff';

    protected $fillable = [
        'code',
        'name',
        'staff_group',
        'user_id',
        'branch_id',
        'monthly_salary',
        'allowance_minutes',
        'way_rate',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'float',
            'allowance_minutes' => 'integer',
            'way_rate' => 'float',
            'sort_order' => 'integer',
            'status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function lateFineRows(): HasMany
    {
        return $this->hasMany(HrLateFineRow::class, 'staff_id');
    }

    public function officeSalaryRows(): HasMany
    {
        return $this->hasMany(HrOfficeSalaryRow::class, 'staff_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Rider staff belonging to မန္တလေး (not Food(မန္တလေး) / Lashio / Yangon).
     */
    public function scopeMandalayRiders($query)
    {
        $branchId = function_exists('mandalayBranchId') ? mandalayBranchId() : defaultDestinationBranchId();
        if (! $branchId) {
            return $query;
        }

        return $query->where(function ($q) use ($branchId) {
            $q->where('hr_staff.branch_id', $branchId)
                ->orWhereHas('user', fn ($u) => $u->where('branch_id', $branchId));
        });
    }
}
