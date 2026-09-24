<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kyo_shin_batches')) {
            Schema::create('kyo_shin_batches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('os_user_id')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('payment_method', 16)->default('kpay');
                $table->string('kpay_name')->nullable();
                $table->string('kpay_no', 50)->nullable();
                $table->string('slip_photo_path')->nullable();
                $table->string('slip_table_path')->nullable();
                $table->date('due_finished_at')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->json('item_ids')->nullable();
                $table->unsignedBigInteger('cash_payout_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('kyo_shin_items') && ! Schema::hasColumn('kyo_shin_items', 'batch_id')) {
            Schema::table('kyo_shin_items', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('id')->index();
                $table->string('payment_method', 16)->nullable()->after('amount');
            });
        }

        if (Schema::hasTable('os_cash_payouts') && ! Schema::hasColumn('os_cash_payouts', 'kyo_shin_batch_id')) {
            Schema::table('os_cash_payouts', function (Blueprint $table) {
                $table->unsignedBigInteger('kyo_shin_batch_id')->nullable()->after('created_by')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('os_cash_payouts') && Schema::hasColumn('os_cash_payouts', 'kyo_shin_batch_id')) {
            Schema::table('os_cash_payouts', function (Blueprint $table) {
                $table->dropColumn('kyo_shin_batch_id');
            });
        }

        if (Schema::hasTable('kyo_shin_items') && Schema::hasColumn('kyo_shin_items', 'batch_id')) {
            Schema::table('kyo_shin_items', function (Blueprint $table) {
                $table->dropColumn(['batch_id', 'payment_method']);
            });
        }

        Schema::dropIfExists('kyo_shin_batches');
    }
};
