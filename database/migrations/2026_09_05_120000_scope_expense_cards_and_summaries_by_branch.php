<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultBranchId = $this->defaultBranchId();

        if (Schema::hasTable('expense_cards')) {
            if ($defaultBranchId) {
                DB::table('expense_cards')
                    ->where(function ($q) {
                        $q->whereNull('branch_id')->orWhere('branch_id', 0);
                    })
                    ->update(['branch_id' => $defaultBranchId]);
            }

            Schema::table('expense_cards', function (Blueprint $table) {
                try {
                    $table->dropUnique('expense_cards_expense_date_unique');
                } catch (\Throwable $e) {
                    // already dropped
                }
            });

            Schema::table('expense_cards', function (Blueprint $table) {
                $table->unique(['expense_date', 'branch_id'], 'expense_cards_date_branch_unique');
            });
        }

        if (Schema::hasTable('expense_summaries')) {
            if (! Schema::hasColumn('expense_summaries', 'branch_id')) {
                Schema::table('expense_summaries', function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('summary_date')->index();
                });
            }

            if ($defaultBranchId) {
                DB::table('expense_summaries')
                    ->where(function ($q) {
                        $q->whereNull('branch_id')->orWhere('branch_id', 0);
                    })
                    ->update(['branch_id' => $defaultBranchId]);

                $cardBranches = DB::table('expense_cards')->pluck('branch_id', 'id');
                foreach ($cardBranches as $cardId => $branchId) {
                    if (! $branchId) {
                        continue;
                    }
                    DB::table('expense_summaries')
                        ->where('expense_card_id', $cardId)
                        ->update(['branch_id' => $branchId]);
                }
            }

            Schema::table('expense_summaries', function (Blueprint $table) {
                try {
                    $table->dropUnique('expense_summaries_summary_date_unique');
                } catch (\Throwable $e) {
                    // already dropped
                }
            });

            Schema::table('expense_summaries', function (Blueprint $table) {
                $table->unique(['summary_date', 'branch_id'], 'expense_summaries_date_branch_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_cards')) {
            Schema::table('expense_cards', function (Blueprint $table) {
                try {
                    $table->dropUnique('expense_cards_date_branch_unique');
                } catch (\Throwable $e) {
                    // already dropped
                }
            });
            Schema::table('expense_cards', function (Blueprint $table) {
                $table->unique('expense_date', 'expense_cards_expense_date_unique');
            });
        }

        if (Schema::hasTable('expense_summaries')) {
            Schema::table('expense_summaries', function (Blueprint $table) {
                try {
                    $table->dropUnique('expense_summaries_date_branch_unique');
                } catch (\Throwable $e) {
                    // already dropped
                }
            });
            Schema::table('expense_summaries', function (Blueprint $table) {
                $table->unique('summary_date', 'expense_summaries_summary_date_unique');
            });
        }
    }

    private function defaultBranchId(): ?int
    {
        $id = DB::table('branches')->where('name', 'မန္တလေး')->value('id');
        if ($id) {
            return (int) $id;
        }

        $id = DB::table('branches')->where('status', 1)->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }
};
