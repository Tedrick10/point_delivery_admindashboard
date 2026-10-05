<?php

use App\Models\ExpenseCard;
use App\Services\ExpenseSummaryService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! class_exists(ExpenseCard::class) || ! class_exists(ExpenseSummaryService::class)) {
            return;
        }

        $service = app(ExpenseSummaryService::class);
        ExpenseCard::query()
            ->whereDoesntHave('summaries')
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get()
            ->each(function (ExpenseCard $card) use ($service) {
                try {
                    $service->generateFromCard($card, $card->created_by);
                } catch (\Throwable $e) {
                    report($e);
                }
            });
    }

    public function down(): void
    {
        // Generated rows are kept; they may already have been edited.
    }
};
