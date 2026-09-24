<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('os_cash_payouts')) {
            return;
        }

        if (! Schema::hasColumn('os_cash_payouts', 'branch_id')) {
            Schema::table('os_cash_payouts', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->index()->after('os_user_id');
            });
        }

        if (! Schema::hasTable('kyo_shin_items')) {
            return;
        }

        $payouts = DB::table('os_cash_payouts')
            ->whereNotNull('kyo_shin_batch_id')
            ->where(function ($q) {
                $q->whereNull('branch_id')->orWhere('branch_id', 0);
            })
            ->get(['id', 'kyo_shin_batch_id']);

        foreach ($payouts as $payout) {
            $branchId = (int) DB::table('kyo_shin_items')
                ->where('batch_id', $payout->kyo_shin_batch_id)
                ->where('branch_id', '>', 0)
                ->value('branch_id');
            if ($branchId > 0) {
                DB::table('os_cash_payouts')->where('id', $payout->id)->update(['branch_id' => $branchId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('os_cash_payouts') && Schema::hasColumn('os_cash_payouts', 'branch_id')) {
            Schema::table('os_cash_payouts', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }
    }
};
