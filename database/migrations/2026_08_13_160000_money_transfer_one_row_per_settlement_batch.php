<?php

use App\Models\OsCashPayout;
use App\Models\OsMoneyTransfer;
use App\Models\OsSettlementBatch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os_money_transfers', function (Blueprint $table) {
            $table->dropUnique('os_money_transfers_period_branch_os_unique');
        });

        Schema::table('os_money_transfers', function (Blueprint $table) {
            $table->unique('settlement_batch_id', 'os_money_transfers_settlement_batch_unique');
        });

        $this->backfillCashRows();
    }

    public function down(): void
    {
        Schema::table('os_money_transfers', function (Blueprint $table) {
            $table->dropUnique('os_money_transfers_settlement_batch_unique');
        });

        Schema::table('os_money_transfers', function (Blueprint $table) {
            $table->unique(
                ['period_from', 'period_to', 'branch_id', 'os_user_id'],
                'os_money_transfers_period_branch_os_unique'
            );
        });
    }

    protected function backfillCashRows(): void
    {
        $batches = OsSettlementBatch::query()
            ->where('payment_method', 'cash')
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            $fromDay = optional($batch->from_date)->toDateString()
                ?? now('Asia/Yangon')->toDateString();
            $toDay = optional($batch->to_date)->toDateString() ?? $fromDay;
            $due = round(abs((float) $batch->amount), 2);

            $transfer = OsMoneyTransfer::query()->updateOrCreate(
                ['settlement_batch_id' => $batch->id],
                [
                    'period_from' => $fromDay,
                    'period_to' => $toDay,
                    'branch_id' => 0,
                    'os_user_id' => (int) $batch->os_user_id,
                    'payment_method' => 'cash',
                    'cash_amount' => $due,
                    'kpay_amount' => 0,
                    'freight_amount' => null,
                ]
            );

            $payout = OsCashPayout::query()->firstOrNew([
                'settlement_batch_id' => $batch->id,
            ]);
            $payout->os_user_id = (int) $batch->os_user_id;
            $payout->money_transfer_id = $transfer->id;
            $payout->period_from = $fromDay;
            $payout->period_to = $toDay;
            $payout->amount = $due;
            if (! $payout->slip_photo_path && $batch->kpay_slip_path) {
                $payout->slip_photo_path = $batch->kpay_slip_path;
            }
            if (! $payout->exists) {
                $payout->status = OsCashPayout::STATUS_UNASSIGNED;
            }
            $payout->save();
        }
    }
};
