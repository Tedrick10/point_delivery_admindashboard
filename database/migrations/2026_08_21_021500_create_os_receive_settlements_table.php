<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os_settlement_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('os_settlement_batches', 'settlement_side')) {
                $table->string('settlement_side', 16)->nullable()->after('payment_method'); // pay|receive
            }
        });

        if (! Schema::hasTable('os_receive_settlements')) {
            Schema::create('os_receive_settlements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('settlement_batch_id')->nullable()->index();
                $table->unsignedBigInteger('os_user_id')->index();
                $table->date('from_date');
                $table->date('to_date');
                $table->double('amount')->default(0);
                $table->string('status', 24)->default('pending')->index(); // pending|waiting|received|rejected
                $table->string('admin_qr_path')->nullable();
                $table->string('os_payslip_path')->nullable();
                $table->text('admin_remark')->nullable();
                $table->unsignedBigInteger('finished_by')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('os_submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('os_receive_settlements');

        Schema::table('os_settlement_batches', function (Blueprint $table) {
            if (Schema::hasColumn('os_settlement_batches', 'settlement_side')) {
                $table->dropColumn('settlement_side');
            }
        });
    }
};
