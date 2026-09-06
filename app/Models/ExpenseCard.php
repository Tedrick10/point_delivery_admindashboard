<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCard extends Model
{
    protected $fillable = [
        'expense_date',
        'branch_id',
        'total_amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'branch_id' => 'integer',
        'total_amount' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(ExpenseSummary::class);
    }

    public function isGenerated(): bool
    {
        return $this->summaries()->exists();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recalculateTotal(): void
    {
        $total = (float) $this->items()->sum('amount');
        $this->forceFill(['total_amount' => round($total, 2)])->save();
    }
}
