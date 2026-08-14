<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os_settlement_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('os_settlement_batches', 'payment_method')) {
                $table->string('payment_method', 16)->default('kpay')->after('amount');
            }
        });

        Schema::table('os_money_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('os_money_transfers', 'payment_method')) {
                $table->string('payment_method', 16)->default('kpay')->after('os_user_id');
            }
            if (! Schema::hasColumn('os_money_transfers', 'settlement_batch_id')) {
                $table->unsignedBigInteger('settlement_batch_id')->nullable()->after('payment_method');
            }
        });

        if (! Schema::hasTable('os_cash_payouts')) {
            Schema::create('os_cash_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('os_user_id')->index();
                $table->unsignedBigInteger('settlement_batch_id')->nullable()->index();
                $table->unsignedBigInteger('money_transfer_id')->nullable()->index();
                $table->date('period_from');
                $table->date('period_to');
                $table->double('amount')->default(0);
                $table->string('status', 24)->default('unassigned')->index(); // unassigned|assigned|pending|done
                $table->unsignedBigInteger('delivery_man_id')->nullable()->index();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('pending_at')->nullable();
                $table->timestamp('done_at')->nullable();
                $table->text('pending_note')->nullable();
                $table->string('pending_photo_path')->nullable();
                $table->text('done_note')->nullable();
                $table->string('done_photo_path')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('os_cash_payouts');

        Schema::table('os_money_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('os_money_transfers', 'settlement_batch_id')) {
                $table->dropColumn('settlement_batch_id');
            }
            if (Schema::hasColumn('os_money_transfers', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });

        Schema::table('os_settlement_batches', function (Blueprint $table) {
            if (Schema::hasColumn('os_settlement_batches', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
