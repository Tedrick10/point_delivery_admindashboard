<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSummary extends Model
{
    protected $fillable = [
        'summary_date',
        'branch_id',
        'expense_card_id',
        'income',
        'expense',
        'ako_given',
        'generated_by',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'branch_id' => 'integer',
        'income' => 'float',
        'expense' => 'float',
        'ako_given' => 'float',
    ];

    public function expenseCard(): BelongsTo
    {
        return $this->belongsTo(ExpenseCard::class);
    }

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
