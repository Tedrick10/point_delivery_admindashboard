<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_kpay_accounts')) {
            Schema::create('admin_kpay_accounts', function (Blueprint $table) {
                $table->id();
                $table->date('settlement_date')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('kpay_name', 191);
                $table->string('kpay_phone', 64);
                $table->decimal('amount', 14, 2)->default(0); // opening amount
                $table->decimal('balance', 14, 2)->default(0); // remaining
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('os_settlement_batches')
            && ! Schema::hasColumn('os_settlement_batches', 'admin_kpay_account_id')) {
            Schema::table('os_settlement_batches', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_kpay_account_id')->nullable()->after('kpay_no')->index();
            });
        }

        if (Schema::hasTable('os_money_transfers')
            && ! Schema::hasColumn('os_money_transfers', 'admin_kpay_account_id')) {
            Schema::table('os_money_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_kpay_account_id')->nullable()->after('settlement_batch_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('os_money_transfers')
            && Schema::hasColumn('os_money_transfers', 'admin_kpay_account_id')) {
            Schema::table('os_money_transfers', function (Blueprint $table) {
                $table->dropColumn('admin_kpay_account_id');
            });
        }

        if (Schema::hasTable('os_settlement_batches')
            && Schema::hasColumn('os_settlement_batches', 'admin_kpay_account_id')) {
            Schema::table('os_settlement_batches', function (Blueprint $table) {
                $table->dropColumn('admin_kpay_account_id');
            });
        }

        Schema::dropIfExists('admin_kpay_accounts');
    }
};
