<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_summary_triple_checks')) {
            return;
        }

        if (! Schema::hasColumn('expense_summary_triple_checks', 'branch_id')) {
            Schema::table('expense_summary_triple_checks', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->default(0)->after('checker_key');
            });
        }

        // Replace global unique with per-branch unique.
        try {
            Schema::table('expense_summary_triple_checks', function (Blueprint $table) {
                $table->dropUnique('expense_summary_triple_checks_date_key');
            });
        } catch (\Throwable $e) {
            // Index may already be gone on some environments.
        }

        $indexExists = collect(DB::select('SHOW INDEX FROM expense_summary_triple_checks'))
            ->contains(fn ($row) => ($row->Key_name ?? '') === 'expense_summary_triple_checks_date_key_branch');

        if (! $indexExists) {
            Schema::table('expense_summary_triple_checks', function (Blueprint $table) {
                $table->unique(
                    ['check_date', 'checker_key', 'branch_id'],
                    'expense_summary_triple_checks_date_key_branch'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_summary_triple_checks')) {
            return;
        }

        try {
            Schema::table('expense_summary_triple_checks', function (Blueprint $table) {
                $table->dropUnique('expense_summary_triple_checks_date_key_branch');
            });
        } catch (\Throwable $e) {
        }

        if (Schema::hasColumn('expense_summary_triple_checks', 'branch_id')) {
            Schema::table('expense_summary_triple_checks', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        Schema::table('expense_summary_triple_checks', function (Blueprint $table) {
            $table->unique(['check_date', 'checker_key'], 'expense_summary_triple_checks_date_key');
        });
    }
};
